<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Inventory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\InventoryService;

class OrderController extends Controller
{
    protected $auditService;

    public function __construct(\App\Services\AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('supplier', 'items');

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->has('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($orders);
        }

        return view('orders.index', compact('orders'));
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required|string|max:255',
            'expected_delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:inventory_master,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }
            $taxAmount = 0;
            $totalAmount = $subtotal + $taxAmount;

            // Create order
            $orderData = [
                'order_number' => PurchaseOrder::generateOrderNumber(),
                'supplier_id' => $validated['supplier_id'] ?? null,
                'supplier_name' => $validated['supplier_name'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'order_date' => now(),
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user() ? $request->user()->username : 'system',
            ];

            // Avoid runtime errors if DB migrations haven't been applied yet
            $columns = Schema::getColumnListing('purchase_orders');
            $orderData = array_intersect_key($orderData, array_flip($columns));

            $order = PurchaseOrder::create($orderData);

            // Create order items
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json($order->load('items'), 201);
            }

            $this->auditService->log(
                $request->user(), 
                'create', 
                'orders', 
                $order->id, 
                "Created Purchase Order #{$order->order_number}", 
                PurchaseOrder::class
            );

            return redirect()->route('orders.index')->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, PurchaseOrder $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,received,cancelled',
        ]);

        $update = ['status' => $validated['status']];
        if ($validated['status'] === 'received') {
            $update['actual_delivery_date'] = now()->toDateString();
        }
        // Check for valid transitions
        $oldStatus = $order->status;
        if ($oldStatus === 'cancelled' || $oldStatus === 'received') {
             if ($request->expectsJson()) {
                 return response()->json(['error' => 'Order is already ' . $oldStatus], 400); 
             }
             return redirect()->back()->with('error', 'Order is already ' . $oldStatus);
        }

        $order->update($update);

        // Audit Log
        $this->auditService->log(
            $request->user(), 
            'update', 
            'orders', 
            $order->id, 
            "Order #{$order->order_number} status updated from {$oldStatus} to {$validated['status']}",
            PurchaseOrder::class,
            ['status' => $oldStatus],
            ['status' => $validated['status']]
        );

        // If order is received, update inventory
        if ($validated['status'] === 'received') {
            DB::beginTransaction();
            try {
                $inventoryService = new InventoryService();
                
                foreach ($order->items as $item) {
                    $product = Inventory::find($item->product_id);
                    if ($product) {
                        // Use Service to calculate WAC and create movement record
                        $inventoryService->receiveStock(
                            $product,
                            $item->quantity,
                            $item->unit_cost, // Crucial: Use actual PO cost
                            'Purchase Order',
                            "Received via PO #{$order->order_number}",
                            $request->user()
                        );
                    }
                }

                // --- ACCOUNTING AUTOMATION (Accounts Payable) ---
                // Record the purchase in the General Ledger (Dr Inventory, Cr AP)
                $accounting = new \App\Services\AccountingService();
                $accounting->recordPurchaseOrder($order, $request->user());
                // -----------------------------------------------

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to update inventory: ' . $e->getMessage()], 500);
            }
        }

        if ($request->expectsJson()) {
            return response()->json($order->load('items'));
        }

        return redirect()->back()->with('success', 'Order status updated');
    }

    /**
     * Process payment for a Purchase Order
     */
    public function storePayment(Request $request, PurchaseOrder $order)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bank,M-Pesa,Cheque',
        ]);

        if ($order->status !== 'received' && $order->status !== 'approved') {
             // You usually pay after receiving, or advance payment.
             // Letting it slide for 'approved' (advance payment) or 'received'.
        }

        if ($order->payment_status === 'paid') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Order is already fully paid'], 400);
            }
            return redirect()->back()->with('error', 'Order is already fully paid');
        }

        DB::beginTransaction();
        try {
            $newAmountPaid = $order->amount_paid + $validated['amount'];
            
            // Prevent overpayment?
            if ($newAmountPaid > $order->total_amount) {
                 // Warning or allow overpayment? Let's block for now.
                 throw new \Exception("Payment amount exceeds outstanding balance.");
            }

            $order->amount_paid = $newAmountPaid;
            $order->payment_status = ($newAmountPaid >= $order->total_amount) ? 'paid' : 'partial';
            $order->save();

            // Record Accounting Entry
            $accounting = new \App\Services\AccountingService();
            $accounting->recordPurchaseOrderPayment($order, $validated['amount'], $validated['payment_method'], $request->user());

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Payment recorded', 'order' => $order]);
            }
            return redirect()->back()->with('success', 'Payment recorded successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Get order suggestions (low stock items)
     */
    public function suggestions(Request $request)
    {
        $threshold = $request->get('threshold');

        $lowStockItemsQuery = Inventory::query();
        if ($threshold !== null && $threshold !== '') {
            $lowStockItemsQuery->where('quantity_in_stock', '<=', (int) $threshold);
        } else {
            $lowStockItemsQuery->whereColumn('quantity_in_stock', '<=', 'min_stock_level');
        }

        $lowStockItems = $lowStockItemsQuery
            ->orderBy('quantity_in_stock')
            ->limit(20)
            ->get();

        return response()->json([
            'low_stock' => $lowStockItems,
            'top_selling' => [], // Can be implemented later
        ]);
    }

    /**
     * Get order dashboard data
     */
    public function dashboard(Request $request)
    {
        $stats = [
            'pending' => PurchaseOrder::where('status', 'pending')->count(),
            'approved' => PurchaseOrder::where('status', 'approved')->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'pending_value' => PurchaseOrder::where('status', 'pending')->sum('total_amount'),
        ];

        $recentOrders = PurchaseOrder::with('supplier')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_orders' => $recentOrders,
        ]);
    }
}
