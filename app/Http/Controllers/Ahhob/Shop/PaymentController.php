<?php

namespace App\Http\Controllers\Ahhob\Shop;

use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * 결제 페이지 표시
     */
    public function show(Order $order): View
    {
        if (!$order->canBePaid()) {
            abort(400, '결제할 수 없는 주문 상태입니다.');
        }

        $activeGateways = $this->gatewayManager->getActiveGateways();
        $paymentMethods = $this->gatewayManager->getAllSupportedMethods();

        return view('ahhob.shop.orders.payment', compact(
            'order',
            'activeGateways',
            'paymentMethods'
        ));
    }

    /**
     * 결제 처리
     */
    public function process(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'payment_gateway' => 'required|string|in:inicis,kg_inicis,stripe',
            'payment_method' => 'required|string',
        ]);

        try {
            $gateway = PaymentGateway::from($request->payment_gateway);
            $paymentMethod = $request->payment_method;

            $response = $this->paymentService->processPayment(
                $order,
                $gateway,
                $paymentMethod,
                $request->only(['customer_info', 'extra_data'])
            );

            if ($response->isRedirectRequired()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $response->redirectUrl,
                    'message' => $response->message,
                ]);
            }

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message ?? ($response->isSuccess() ? '결제가 완료되었습니다.' : '결제에 실패했습니다.'),
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment process failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '결제 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 결제 성공 페이지
     */
    public function success(Request $request, Order $order): View|RedirectResponse
    {
        $transactionId = $request->query('transaction_id');
        
        if ($transactionId) {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->where('order_id', $order->id)
                ->first();

            if ($transaction && $transaction->status->isSuccess()) {
                return view('ahhob.shop.orders.payment-success', compact('order', 'transaction'));
            }
        }

        return redirect()->route('ahhob.shop.orders.show', $order)
            ->with('error', '결제 정보를 확인할 수 없습니다.');
    }

    /**
     * 결제 실패 페이지
     */
    public function failure(Request $request, Order $order): View
    {
        $errorMessage = $request->query('error_message', '결제에 실패했습니다.');
        
        return view('ahhob.shop.orders.payment-failure', compact('order', 'errorMessage'));
    }

    /**
     * 결제 취소 페이지
     */
    public function cancel(Request $request, Order $order): View
    {
        return view('ahhob.shop.orders.payment-cancel', compact('order'));
    }

    /**
     * 결제 상태 조회
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        try {
            $response = $this->paymentService->getPaymentStatus($request->transaction_id);

            return response()->json([
                'success' => $response->isSuccess(),
                'status' => $response->status->value,
                'message' => $response->message,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status inquiry failed', [
                'transaction_id' => $request->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '결제 상태 조회 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 결제 취소
     */
    public function cancelPayment(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $response = $this->paymentService->cancelPayment(
                $request->transaction_id,
                $request->reason ?? '고객 요청'
            );

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment cancellation failed', [
                'transaction_id' => $request->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '결제 취소 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 환불 처리
     */
    public function refund(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $response = $this->paymentService->refundPayment(
                $request->transaction_id,
                $request->amount,
                $request->reason
            );

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'transaction_id' => $request->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '환불 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 결제 재시도
     */
    public function retry(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        try {
            $response = $this->paymentService->retryPayment($request->transaction_id);

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment retry failed', [
                'transaction_id' => $request->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '결제 재시도 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 웹훅 처리
     */
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $paymentGateway = PaymentGateway::from($gateway);
            $signature = $request->header('Stripe-Signature') ?: $request->header('X-Signature');
            
            $success = $this->paymentService->handleWebhook(
                $paymentGateway,
                $request->all(),
                $signature
            );

            if ($success) {
                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'failed'], 400);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * 사용 가능한 결제 수단 조회
     */
    public function availableMethods(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $amount = (int) $request->amount;
            $allMethods = $this->gatewayManager->getAllSupportedMethods();
            $availableMethods = [];

            foreach ($allMethods as $method => $info) {
                $availableGateways = $this->gatewayManager->getAvailableGateways($amount, $method);
                
                if (!empty($availableGateways)) {
                    $availableMethods[$method] = [
                        'label' => $info['label'],
                        'gateways' => array_map(fn($g) => [
                            'value' => $g->value,
                            'label' => $g->label(),
                        ], $availableGateways),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'methods' => $availableMethods,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '결제 수단 조회 중 오류가 발생했습니다.',
            ], 500);
        }
    }
}