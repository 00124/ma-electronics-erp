<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductStockImportController extends ApiBaseController
{
    public function warehouses(Request $request)
    {
        $warehouses = Warehouse::select('id', 'name')->orderBy('name')->get();
        return response()->json(['warehouses' => $warehouses]);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Remove header row
        $headers = array_shift($rows);

        // Filter rows where Item Name (col 1) is not empty
        $data = array_values(array_filter($rows, fn($r) => !empty(trim((string)($r[1] ?? '')))));

        $withStock = count(array_filter($data, fn($r) => is_numeric($r[2] ?? '') && (float)$r[2] > 0));

        $preview = array_map(fn($r) => [
            'item_code' => $r[0] ?? '',
            'name'      => $r[1] ?? '',
            'qty'       => is_numeric($r[2] ?? '') ? (float)$r[2] : 0,
            'category'  => $r[3] ?? '',
            'brand'     => $r[4] ?? '',
        ], array_slice($data, 0, 15));

        return response()->json([
            'preview'    => $preview,
            'total_rows' => count($data),
            'with_stock' => $withStock,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:xlsx,xls,csv',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'import_mode'  => 'required|in:all,stock_only',
        ]);

        $warehouseId = (int)$request->input('warehouse_id');
        $importMode  = $request->input('import_mode');
        $companyId   = company()->id;
        $userId      = user()->id;

        $defaultUnit = Unit::first();
        if (!$defaultUnit) {
            return response()->json(['error' => 'No unit found in system. Please create a unit first.'], 422);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        array_shift($rows); // drop header

        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Pre-load categories and brands keyed by name for speed
        $categoryCache = Category::where('company_id', $companyId)->pluck('id', 'name')->toArray();
        $brandCache     = Brand::where('company_id', $companyId)->pluck('id', 'name')->toArray();

        DB::transaction(function () use (
            $rows, $warehouseId, $companyId, $userId, $defaultUnit,
            $importMode, &$results, &$categoryCache, &$brandCache
        ) {
            foreach ($rows as $index => $row) {
                $name = trim((string)($row[1] ?? ''));
                if ($name === '') {
                    continue;
                }

                $rowNum   = $index + 2;
                $itemCode = trim((string)($row[0] ?? ''));
                $qty      = is_numeric($row[2] ?? '') ? (float)$row[2] : 0;
                $catName  = trim((string)($row[3] ?? ''));
                $brandName = trim((string)($row[4] ?? ''));

                try {
                    // ── Category ──────────────────────────────────────────
                    $categoryId = null;
                    if ($catName !== '') {
                        if (!isset($categoryCache[$catName])) {
                            $baseSlug = Str::slug($catName);
                            $slug = $baseSlug;
                            $n = 1;
                            while (Category::where('slug', $slug)->exists()) {
                                $slug = $baseSlug . '-' . $n++;
                            }
                            $cat = Category::create([
                                'company_id' => $companyId,
                                'name'       => $catName,
                                'slug'       => $slug,
                            ]);
                            $categoryCache[$catName] = $cat->id;
                        }
                        $categoryId = $categoryCache[$catName];
                    }

                    // ── Brand ─────────────────────────────────────────────
                    $brandId = null;
                    if ($brandName !== '') {
                        if (!isset($brandCache[$brandName])) {
                            $baseSlug = Str::slug($brandName);
                            $slug = $baseSlug;
                            $n = 1;
                            while (Brand::where('slug', $slug)->exists()) {
                                $slug = $baseSlug . '-' . $n++;
                            }
                            $b = Brand::create([
                                'company_id' => $companyId,
                                'name'       => $brandName,
                                'slug'       => $slug,
                            ]);
                            $brandCache[$brandName] = $b->id;
                        }
                        $brandId = $brandCache[$brandName];
                    }

                    // ── Find existing product ─────────────────────────────
                    $product = null;
                    if ($itemCode !== '') {
                        $product = Product::where('item_code', $itemCode)->first();
                    }
                    if (!$product) {
                        $product = Product::where('name', $name)->first();
                    }

                    if ($product) {
                        // ── Update stock in warehouse ─────────────────────
                        $detail = ProductDetails::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if ($detail) {
                            $detail->current_stock  = $qty;
                            $detail->opening_stock  = $qty;
                            $detail->save();
                        } else {
                            ProductDetails::create([
                                'product_id'         => $product->id,
                                'warehouse_id'       => $warehouseId,
                                'current_stock'      => $qty,
                                'opening_stock'      => $qty,
                                'opening_stock_date' => now()->toDateString(),
                                'status'             => 'enabled',
                            ]);
                        }
                        $results['updated']++;
                    } else {
                        if ($importMode === 'stock_only') {
                            $results['skipped']++;
                            continue;
                        }

                        // ── Create new product ────────────────────────────
                        $baseSlug = Str::slug($name);
                        $slug = $baseSlug;
                        $n = 1;
                        while (Product::where('slug', $slug)->exists()) {
                            $slug = $baseSlug . '-' . $n++;
                        }

                        // Ensure unique item_code
                        $finalItemCode = $itemCode ?: '';
                        if ($finalItemCode === '' || Product::where('item_code', $finalItemCode)->exists()) {
                            $finalItemCode = 'IMP-' . strtoupper(Str::random(6));
                        }

                        $product = Product::create([
                            'company_id'        => $companyId,
                            'warehouse_id'      => $warehouseId,
                            'name'              => $name,
                            'slug'              => $slug,
                            'item_code'         => $finalItemCode,
                            'product_type'      => 'single',
                            'barcode_symbology' => 'CODE128',
                            'category_id'       => $categoryId,
                            'brand_id'          => $brandId,
                            'unit_id'           => $defaultUnit->id,
                            'user_id'           => $userId,
                        ]);

                        ProductDetails::create([
                            'product_id'         => $product->id,
                            'warehouse_id'       => $warehouseId,
                            'current_stock'      => $qty,
                            'opening_stock'      => $qty,
                            'opening_stock_date' => now()->toDateString(),
                            'status'             => 'enabled',
                            'mrp'                => 0,
                            'purchase_price'     => 0,
                            'sales_price'        => 0,
                        ]);

                        $results['created']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Row {$rowNum} ({$name}): " . $e->getMessage();
                    if (count($results['errors']) >= 20) {
                        break;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
