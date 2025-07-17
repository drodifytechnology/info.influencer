<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Review;
use App\Models\Refund;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\CouponCodeMail;
use App\Models\OrderCoupon;
use App\Models\packageOrder;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AcnooPackageOrderController extends Controller
{
    use HasUploader;

    public function index()
    {
        $order = packageOrder::with('service:id,title,category_id,images', 'service.category:id,name', 'user:id,name,image', 'service.user:id,name,image', 'review:id,rating,order_id')->where('user_id', auth()->id())->latest();

        return response()->json([
            'message' => 'package Order fetched successfully',
            'data' => $order
        ]);
    }
   

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required',
             'influencer_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'influencer');
                }),
            ],
            'amount' => 'required',
            'discount_amount' => 'nullable',
            'total_amount' => 'required',
            'description' => 'nullable|string',
        ]);

        $user = auth()->user();

        $order = packageOrder::create( [
                    'user_id' => $user->id,
                    'service_id' => $request->service_id,
                    'influencer_id' => $request->influencer_id,
                    'gateway_id' => $request->gateway_id,
                    'amount' => $request->amount,
                    'discount_amount' => $request->discount_amount??0,
                    'total_amount' => $request->total_amount,
                    'description' => $request->description??''
          ]);

        //generate coupon
        $service = Service::find($request->service_id);
        $code = '';
        if($service->story != 0){
            $random = strtoupper(Str::random(6)); // 6-character code
            $code = 'STORY-' . $random;
           OrderCoupon::create(['title' => 'story' , 'code' => 'STORY-' . $random , 'status' => 'active' ,'order_id' => $order->id , 'service_id' => $request->service_id , 'amount' => $service->story , 'user_id' => auth()->id()]);
        }
        if($service->reels != 0){
            $random = strtoupper(Str::random(6)); // 6-character code
            $code = $code.',REEL-' . $random ;
            OrderCoupon::create(['title' => 'reels' , 'code' => 'REEL-' . $random , 'status' => 'active' ,'order_id' => $order->id , 'service_id' => $request->service_id , 'amount' => $service->reels , 'user_id' => auth()->id()]);
        }
        if($service->post != 0){
            $random = strtoupper(Str::random(6)); // 6-character code
            $code = $code.',POST-' . $random ;
            OrderCoupon::create(['title' => 'post' , 'code' => 'POST-' . $random , 'status' => 'active' , 'order_id' => $order->id , 'service_id' => $request->service_id , 'amount' => $service->post , 'user_id' => auth()->id()]);

        }
          $data = [
                    'code' => $code,
                    'name' => auth()->user()->name,
                ];

                if (env('MAIL_USERNAME')) {
                    if (env('QUEUE_MAIL')) {
                        Mail::to(auth()->user()->email)->queue(new CouponCodeMail($data));
                    } else {
                        Mail::to(auth()->user()->email)->send(new CouponCodeMail($data));
                    }
                } else {
                    return response()->json([
                        'message' => __('Mail service is not configured. Please contact your administrator.'),
                    ], 406);
                }

             
          sendNotification($order->id, route('admin.orders.index', ['id' => $order->id]), notify_users([$order->user_id, $order->influencer_id]), admin_msg:__('New package order created.'), influ_msg:__('You have new package order'), client_msg:__('New package order has been created'));

        return response([
            'message' => 'Package Order saved successfully.An coupon code has been sent to your email. Please check and confirm. ',
            'payment_url' => route('payment-gateways.index', $order->id),
            'data' => $order,
        ]);
    }

    public function show($id)
    {
        $review = Review::where('order_id', $id)->where('user_id', auth()->id())->first();
        $order = packageOrder::with('user:id,name,image', 'influencer:id,name,image', 'service:id,title,images,category_id', 'service.category:id,name', 'gateway:id,name')->findOrFail($id);

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $order,
            'review' => $review,
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $order = packageOrder::with('influencer', 'user:id,device_token', 'service:id,title')->findOrFail($id);

        $order->influencer->update([
            'balance' => $order->influencer->balance + ($order->total_amount - $order->charge)
        ]);

        sendPushNotify(
            'Package Order Approved',
            'Your order titled "' . $order->service->title . '" has been approved successfully.',
            $order->influencer->device_token
        );

        sendPushNotify(
            'Package Order Approved',
            'Your order titled "' . $order->service->title . '" has been approved by the influencer.',
            $order->user->device_token
        );

        sendNotification($order->id, route('admin.orders.index', ['id' => $order->id]), notify_users([$order->user_id, $order->influencer_id]), admin_msg:__('Influencer approved a order'), influ_msg:__('Approved has been successfully'), client_msg:__('Your order has been approved'));

        $order->update([
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Order Approve successfully',
            'data' =>  $order->status
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $order_reject = packageOrder::with('influencer', 'user:id,device_token', 'service:id,title')->findOrFail($id);

        if ($order_reject) {
            if ($order_reject->payment_status === 'paid') {

                $order_reject->update([
                    'status' => 'canceled',
                    'reason' => $request->reason,
                ]);

                Refund::create([
                    'order_id' => $order_reject->id,
                    'status' => 'pending',
                    'reason' => $request->reason,
                ]);

                sendPushNotify(
                    'Package Order Rejected',
                    'Your order titled "' . $order_reject->service->title . '" has been rejected',
                    $order_reject->influencer->device_token
                );

                sendPushNotify(
                    'Package Order Rejected',
                    'Your order titled "' . $order_reject->service->title . '" has been rejected by the influencer. A refund is being processed.',
                    $order_reject->user->device_token
                );

                sendNotification($order_reject->id, route('admin.orders.index', ['id' => $order_reject->id]), notify_users([$order_reject->user_id, $order_reject->influencer_id]), admin_msg:__('Influencer rejected a order'), influ_msg:__('Package Order reject successfully'), client_msg:__('Influencer rejected your Package order. A refund is being processed. Reason: ' . $order_reject->reason), );

                return response()->json([
                    'message' => 'Package Order rejected successfully',
                    'data' => $order_reject->status
                ]);
            } else {
                return response()->json([
                    'message' => 'Order not found'
                ]);
            }
        } else {
            return response()->json([
                'message' => 'Order not found'
            ]);
        }
    }

   
    public function client_cancel(Request $request, string $id)
    {

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $order_cancel = packageOrder::with('influencer:id,device_token', 'user:id,device_token', 'service:id,title')->findOrFail($id);

        if ($order_cancel) {

            $order_cancel->update([
                'status' => 'canceled',
                'reason' => $request->reason,
            ]);

            sendPushNotify(
                'Package Order Canceled',
                'Your order titled "' . $order_cancel->service->title . '" has been canceled by the client. Reason: ' . $order_cancel->reason,
                $order_cancel->user->device_token
            );

            sendPushNotify(
                'Package Order Canceled',
                'The client has canceled your order titled "' . $order_cancel->service->title . '". Reason: ' . $order_cancel->reason,
                $order_cancel->influencer->device_token
            );

            sendNotification($order_cancel->id, route('admin.orders.index', ['id' => $order_cancel->id]), notify_users([$order_cancel->influencer_id, $order_cancel->user_id ]), admin_msg:__('Client rejected a Package order'), influ_msg:__('The client has canceled the Package order. Reason: ' . $order_cancel->reason), client_msg:__('Package Order rejected successfully'));

            return response()->json([
                'message' => 'Package Order cancel successfully',
                'data' =>  $order_cancel->status
            ]);
        } else {
            return response()->json(['message' => 'Order not found'], 404);
        }
    }

}
