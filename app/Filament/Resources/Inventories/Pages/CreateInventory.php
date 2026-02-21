<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Enums\InventoryItemType;
use App\Filament\Resources\Inventories\InventoryResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $itemType = (string) ($data['item_type'] ?? '');
            $categoryId = $this->resolveExpenseCategoryId($itemType);
            $amount = $this->resolveExpenseAmount($data);

            $expensePayload = null;

            if ($categoryId !== null && $amount > 0) {
                $expensePayload = [
                    'category_id' => (int) $categoryId,
                    'amount' => $amount,
                    'expenses_date' => $this->resolveExpenseDate($data),
                    'description' => 'Purchase inventory: ' . ($data['name'] ?? 'Item'),
                ];
            }

            /** @var Model $record */
            $record = static::getModel()::query()->create($data);

            if ($expensePayload) {
                $expense = Expense::query()->create($expensePayload);
                $record->update(['expense_id' => $expense->id]);
            }

            return $record;
        });
    }

    private function resolveExpenseAmount(array $data): float
    {
        $itemType = $data['item_type'] ?? null;

        if ($itemType === InventoryItemType::Asset->value) {
            return (float) ($data['purchase_price'] ?? 0);
        }

        $costPrice = (float) ($data['cost_price'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);

        return max(0, $costPrice * max(0, $quantity));
    }

    private function resolveExpenseDate(array $data): string
    {
        if (! empty($data['purchase_date'])) {
            return (string) $data['purchase_date'];
        }

        return now()->toDateString();
    }

    private function resolveExpenseCategoryId(string $itemType): ?int
    {
        $aliases = match ($itemType) {
            InventoryItemType::Asset->value => ['asset', 'assets', 'fixed asset', 'fixed_assets'],
            InventoryItemType::Consumable->value => ['consumable', 'consumables'],
            InventoryItemType::Retail->value => ['retail', 'retails'],
            default => [$itemType],
        };

        $categories = ExpenseCategory::query()->get(['id', 'name']);

        foreach ($aliases as $alias) {
            $normalizedAlias = Str::of($alias)->lower()->replace(['_', '-'], ' ')->squish()->value();
            $match = $categories->first(function ($category) use ($normalizedAlias) {
                $normalizedCategory = Str::of((string) $category->name)->lower()->replace(['_', '-'], ' ')->squish()->value();
                return $normalizedCategory === $normalizedAlias;
            });

            if ($match) {
                return (int) $match->id;
            }
        }

        return null;
    }
}
