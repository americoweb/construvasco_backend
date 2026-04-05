#!/bin/bash

# iOPS Amazing Brindes - Products Module Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "🎨 Setting up Amazing Brindes Products Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create main directory structure
echo "📁 Creating directory structure..."

# App directories
mkdir -p app/Http/Controllers/Product
mkdir -p app/Services/Product
mkdir -p app/Repositories/Product/Contracts
mkdir -p app/Repositories/Product/Eloquent
mkdir -p app/Models/Product
mkdir -p app/Http/Requests/Product
mkdir -p app/Http/Resources/Product
mkdir -p app/Events/Product
mkdir -p app/Enums/Product
mkdir -p app/Traits
mkdir -p app/Exceptions

# Database directories
mkdir -p database/migrations
mkdir -p database/factories/Product
mkdir -p database/seeders

# Routes
mkdir -p routes

# Tests
mkdir -p tests/Unit/Services/Product
mkdir -p tests/Feature/Product

echo "✅ Directory structure created!"

# ============================================
# ENUMS
# ============================================
echo "🏷️ Creating Enums..."

cat > app/Enums/Product/ProductStatus.php << 'EOF'
<?php

namespace App\Enums\Product;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case OUT_OF_STOCK = 'out_of_stock';
    case DISCONTINUED = 'discontinued';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::OUT_OF_STOCK => 'Fora de Estoque',
            self::DISCONTINUED => 'Descontinuado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::OUT_OF_STOCK => 'yellow',
            self::DISCONTINUED => 'red',
        };
    }
}
EOF

cat > app/Enums/Product/PrintAreaPosition.php << 'EOF'
<?php

namespace App\Enums\Product;

enum PrintAreaPosition: string
{
    case FRONT_CHEST = 'front_chest';
    case BACK_FULL = 'back_full';
    case FULL_WRAP = 'full_wrap';
    case FRONT = 'front';
    case BACK = 'back';
    case LEFT_CHEST = 'left_chest';
    case FRONT_COVER = 'front_cover';
    case BODY = 'body';
    case FULL_POSTER = 'full_poster';

    public function label(): string
    {
        return match($this) {
            self::FRONT_CHEST => 'Frente (Peito)',
            self::BACK_FULL => 'Costas (Completo)',
            self::FULL_WRAP => 'Envolvente Completo',
            self::FRONT => 'Frente',
            self::BACK => 'Costas',
            self::LEFT_CHEST => 'Peito Esquerdo',
            self::FRONT_COVER => 'Capa Frontal',
            self::BODY => 'Corpo',
            self::FULL_POSTER => 'Poster Completo',
        };
    }
}
EOF

# ============================================
# MODELS
# ============================================
echo "📦 Creating Models..."

cat > app/Models/Product/Product.php << 'EOF'
<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\Product\ProductStatus;
use App\Traits\HasUuid;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'min_quantity',
        'image_url',
        'base_image_url',
        'design_hint',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'status' => ProductStatus::class,
    ];

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function printAreas(): HasMany
    {
        return $this->hasMany(ProductPrintArea::class)->orderBy('sort_order');
    }

    public function activeColors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function activePrintAreas(): HasMany
    {
        return $this->hasMany(ProductPrintArea::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function calculateTotal(int $quantity): float
    {
        return $this->price * max($quantity, $this->min_quantity);
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::ACTIVE;
    }
}
EOF

cat > app/Models/Product/ProductColor.php << 'EOF'
<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'hex_code',
        'is_active',
        'sort_order',
        'stock_quantity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'stock_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock_quantity === null || $this->stock_quantity >= $quantity;
    }
}
EOF

cat > app/Models/Product/ProductPrintArea.php << 'EOF'
<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Product\PrintAreaPosition;

class ProductPrintArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
        'description',
        'max_width_cm',
        'max_height_cm',
        'additional_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'position' => PrintAreaPosition::class,
        'max_width_cm' => 'decimal:2',
        'max_height_cm' => 'decimal:2',
        'additional_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDimensionsAttribute(): string
    {
        return "{$this->max_width_cm}cm x {$this->max_height_cm}cm";
    }
}
EOF

# ============================================
# TRAITS
# ============================================
echo "🔧 Creating Traits..."

cat > app/Traits/HasUuid.php << 'EOF'
<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
EOF

# ============================================
# REPOSITORY CONTRACTS
# ============================================
echo "📋 Creating Repository Contracts..."

cat > app/Repositories/Product/Contracts/ProductRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(array $filters = []): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function findById(int $id): ?Product;
    
    public function findByUuid(string $uuid): ?Product;
    
    public function findBySlug(string $slug): ?Product;
    
    public function create(array $data): Product;
    
    public function update(int $id, array $data): Product;
    
    public function delete(int $id): bool;
    
    public function getActive(): Collection;
    
    public function getFeatured(): Collection;
    
    public function getWithColorsAndAreas(int $id): ?Product;
}
EOF

cat > app/Repositories/Product/Contracts/ProductColorRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Collection;

interface ProductColorRepositoryInterface
{
    public function findById(int $id): ?ProductColor;
    
    public function getByProduct(int $productId): Collection;
    
    public function create(array $data): ProductColor;
    
    public function update(int $id, array $data): ProductColor;
    
    public function delete(int $id): bool;
    
    public function updateStock(int $id, int $quantity): ProductColor;
}
EOF

cat > app/Repositories/Product/Contracts/ProductPrintAreaRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\ProductPrintArea;
use Illuminate\Database\Eloquent\Collection;

interface ProductPrintAreaRepositoryInterface
{
    public function findById(int $id): ?ProductPrintArea;
    
    public function getByProduct(int $productId): Collection;
    
    public function create(array $data): ProductPrintArea;
    
    public function update(int $id, array $data): ProductPrintArea;
    
    public function delete(int $id): bool;
}
EOF

# ============================================
# REPOSITORY IMPLEMENTATIONS
# ============================================
echo "🗃️ Creating Repository Implementations..."

cat > app/Repositories/Product/Eloquent/EloquentProductRepository.php << 'EOF'
<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\Product;
use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Enums\Product\ProductStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

    public function all(array $filters = []): Collection
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->ordered()->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->ordered()->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Product
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->findById($id);
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);
        return $product->delete();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getFeatured(): Collection
    {
        return $this->model->active()->featured()->ordered()->get();
    }

    public function getWithColorsAndAreas(int $id): ?Product
    {
        return $this->model->with(['activeColors', 'activePrintAreas'])->find($id);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
    }
}
EOF

cat > app/Repositories/Product/Eloquent/EloquentProductColorRepository.php << 'EOF'
<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\ProductColor;
use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductColorRepository implements ProductColorRepositoryInterface
{
    public function __construct(
        protected ProductColor $model
    ) {}

    public function findById(int $id): ?ProductColor
    {
        return $this->model->find($id);
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): ProductColor
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ProductColor
    {
        $color = $this->findById($id);
        $color->update($data);
        return $color->fresh();
    }

    public function delete(int $id): bool
    {
        $color = $this->findById($id);
        return $color->delete();
    }

    public function updateStock(int $id, int $quantity): ProductColor
    {
        $color = $this->findById($id);
        $color->stock_quantity = $quantity;
        $color->save();
        return $color;
    }
}
EOF

cat > app/Repositories/Product/Eloquent/EloquentProductPrintAreaRepository.php << 'EOF'
<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\ProductPrintArea;
use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductPrintAreaRepository implements ProductPrintAreaRepositoryInterface
{
    public function __construct(
        protected ProductPrintArea $model
    ) {}

    public function findById(int $id): ?ProductPrintArea
    {
        return $this->model->find($id);
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): ProductPrintArea
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ProductPrintArea
    {
        $area = $this->findById($id);
        $area->update($data);
        return $area->fresh();
    }

    public function delete(int $id): bool
    {
        $area = $this->findById($id);
        return $area->delete();
    }
}
EOF

# ============================================
# SERVICES
# ============================================
echo "⚙️ Creating Services..."

cat > app/Services/Product/ProductService.php << 'EOF'
<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product\Product;
use App\Enums\Product\ProductStatus;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters, $perPage);
    }

    public function getAll(array $filters = []): Collection
    {
        return $this->productRepository->all($filters);
    }

    public function findById(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function findByUuid(string $uuid): Product
    {
        $product = $this->productRepository->findByUuid($uuid);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function findBySlug(string $slug): Product
    {
        $product = $this->productRepository->findBySlug($slug);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function create(array $data): Product
    {
        $data['slug'] = $this->generateSlug($data['name']);
        
        if (!isset($data['status'])) {
            $data['status'] = ProductStatus::ACTIVE;
        }
        
        return $this->productRepository->create($data);
    }

    public function update(int $id, array $data): Product
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name'], $id);
        }
        
        return $this->productRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function getActiveProducts(): Collection
    {
        return $this->productRepository->getActive();
    }

    public function getFeaturedProducts(): Collection
    {
        return $this->productRepository->getFeatured();
    }

    public function getProductWithDetails(int $id): Product
    {
        $product = $this->productRepository->getWithColorsAndAreas($id);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function calculateOrderTotal(int $productId, int $quantity): array
    {
        $product = $this->findById($productId);
        
        $effectiveQuantity = max($quantity, $product->min_quantity);
        $totalPrice = $product->price * $effectiveQuantity;
        
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $effectiveQuantity,
            'min_quantity' => $product->min_quantity,
            'total_price' => $totalPrice,
            'currency' => 'MT',
        ];
    }

    public function updateStatus(int $id, ProductStatus $status): Product
    {
        return $this->productRepository->update($id, ['status' => $status]);
    }

    public function toggleFeatured(int $id): Product
    {
        $product = $this->findById($id);
        
        return $this->productRepository->update($id, [
            'is_featured' => !$product->is_featured
        ]);
    }

    protected function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $product = $this->productRepository->findBySlug($slug);
        
        if (!$product) {
            return false;
        }
        
        if ($excludeId && $product->id === $excludeId) {
            return false;
        }
        
        return true;
    }
}
EOF

cat > app/Services/Product/ProductColorService.php << 'EOF'
<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Collection;

class ProductColorService
{
    public function __construct(
        protected ProductColorRepositoryInterface $colorRepository
    ) {}

    public function getProductColors(int $productId): Collection
    {
        return $this->colorRepository->getByProduct($productId);
    }

    public function findById(int $id): ProductColor
    {
        $color = $this->colorRepository->findById($id);
        
        if (!$color) {
            throw new \Exception('Cor não encontrada');
        }
        
        return $color;
    }

    public function create(int $productId, array $data): ProductColor
    {
        $data['product_id'] = $productId;
        
        return $this->colorRepository->create($data);
    }

    public function update(int $id, array $data): ProductColor
    {
        return $this->colorRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->colorRepository->delete($id);
    }

    public function updateStock(int $id, int $quantity): ProductColor
    {
        return $this->colorRepository->updateStock($id, $quantity);
    }

    public function decrementStock(int $id, int $quantity): ProductColor
    {
        $color = $this->findById($id);
        
        if ($color->stock_quantity !== null) {
            $newQuantity = max(0, $color->stock_quantity - $quantity);
            return $this->colorRepository->updateStock($id, $newQuantity);
        }
        
        return $color;
    }

    public function checkAvailability(int $id, int $requiredQuantity): bool
    {
        $color = $this->findById($id);
        return $color->hasStock($requiredQuantity);
    }
}
EOF

cat > app/Services/Product/ProductPrintAreaService.php << 'EOF'
<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use App\Models\Product\ProductPrintArea;
use Illuminate\Database\Eloquent\Collection;

class ProductPrintAreaService
{
    public function __construct(
        protected ProductPrintAreaRepositoryInterface $printAreaRepository
    ) {}

    public function getProductPrintAreas(int $productId): Collection
    {
        return $this->printAreaRepository->getByProduct($productId);
    }

    public function findById(int $id): ProductPrintArea
    {
        $area = $this->printAreaRepository->findById($id);
        
        if (!$area) {
            throw new \Exception('Área de impressão não encontrada');
        }
        
        return $area;
    }

    public function create(int $productId, array $data): ProductPrintArea
    {
        $data['product_id'] = $productId;
        
        return $this->printAreaRepository->create($data);
    }

    public function update(int $id, array $data): ProductPrintArea
    {
        return $this->printAreaRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->printAreaRepository->delete($id);
    }
}
EOF

# ============================================
# HTTP REQUESTS (Form Validation)
# ============================================
echo "✅ Creating Form Requests..."

cat > app/Http/Requests/Product/CreateProductRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\ProductStatus;
use Illuminate\Validation\Rules\Enum;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'min_quantity' => 'required|integer|min:1',
            'image_url' => 'nullable|url|max:500',
            'base_image_url' => 'nullable|url|max:500',
            'design_hint' => 'nullable|string|max:1000',
            'status' => ['nullable', new Enum(ProductStatus::class)],
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório',
            'price.required' => 'O preço é obrigatório',
            'price.numeric' => 'O preço deve ser um número válido',
            'min_quantity.required' => 'A quantidade mínima é obrigatória',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1',
        ];
    }
}
EOF

cat > app/Http/Requests/Product/UpdateProductRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\ProductStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'min_quantity' => 'sometimes|integer|min:1',
            'image_url' => 'nullable|url|max:500',
            'base_image_url' => 'nullable|url|max:500',
            'design_hint' => 'nullable|string|max:1000',
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
EOF

cat > app/Http/Requests/Product/CreateProductColorRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'hex_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da cor é obrigatório',
            'hex_code.required' => 'O código hexadecimal é obrigatório',
            'hex_code.regex' => 'O código hexadecimal deve estar no formato #RRGGBB',
        ];
    }
}
EOF

cat > app/Http/Requests/Product/CreateProductPrintAreaRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\PrintAreaPosition;
use Illuminate\Validation\Rules\Enum;

class CreateProductPrintAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'position' => ['required', new Enum(PrintAreaPosition::class)],
            'description' => 'nullable|string|max:500',
            'max_width_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'additional_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da área de impressão é obrigatório',
            'position.required' => 'A posição é obrigatória',
        ];
    }
}
EOF

# ============================================
# HTTP RESOURCES (API Response Formatting)
# ============================================
echo "📤 Creating API Resources..."

cat > app/Http/Resources/Product/ProductResource.php << 'EOF'
<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2, ',', '.') . ' MT',
            'min_quantity' => $this->min_quantity,
            'image_url' => $this->image_url,
            'base_image_url' => $this->base_image_url,
            'design_hint' => $this->design_hint,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_featured' => $this->is_featured,
            'is_available' => $this->isAvailable(),
            'sort_order' => $this->sort_order,
            'colors' => ProductColorResource::collection($this->whenLoaded('colors')),
            'active_colors' => ProductColorResource::collection($this->whenLoaded('activeColors')),
            'print_areas' => ProductPrintAreaResource::collection($this->whenLoaded('printAreas')),
            'active_print_areas' => ProductPrintAreaResource::collection($this->whenLoaded('activePrintAreas')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Product/ProductListResource.php << 'EOF'
<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2, ',', '.') . ' MT',
            'min_quantity' => $this->min_quantity,
            'image_url' => $this->image_url,
            'status' => $this->status->value,
            'is_featured' => $this->is_featured,
            'is_available' => $this->isAvailable(),
        ];
    }
}
EOF

cat > app/Http/Resources/Product/ProductColorResource.php << 'EOF'
<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hex_code' => $this->hex_code,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'stock_quantity' => $this->stock_quantity,
            'has_stock' => $this->hasStock(),
        ];
    }
}
EOF

cat > app/Http/Resources/Product/ProductPrintAreaResource.php << 'EOF'
<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPrintAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position->value,
            'position_label' => $this->position->label(),
            'description' => $this->description,
            'max_width_cm' => (float) $this->max_width_cm,
            'max_height_cm' => (float) $this->max_height_cm,
            'dimensions' => $this->dimensions,
            'additional_price' => (float) $this->additional_price,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
EOF

# ============================================
# CONTROLLERS
# ============================================
echo "🎮 Creating Controllers..."

cat > app/Http/Controllers/Product/ProductController.php << 'EOF'
<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => ProductListResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Produto criado com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($id);

        return response()->json([
            'data' => new ProductResource($product)
        ]);
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);
        $product->load(['activeColors', 'activePrintAreas']);

        return response()->json([
            'data' => new ProductResource($product)
        ]);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Produto atualizado com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->delete($id);

        return response()->json([
            'message' => 'Produto excluído com sucesso'
        ]);
    }

    public function active(): JsonResponse
    {
        $products = $this->productService->getActiveProducts();

        return response()->json([
            'data' => ProductListResource::collection($products)
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = $this->productService->getFeaturedProducts();

        return response()->json([
            'data' => ProductListResource::collection($products)
        ]);
    }

    public function toggleFeatured(int $id): JsonResponse
    {
        $product = $this->productService->toggleFeatured($id);

        return response()->json([
            'data' => new ProductResource($product),
            'message' => $product->is_featured ? 'Produto destacado' : 'Produto removido dos destaques'
        ]);
    }

    public function calculatePrice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $calculation = $this->productService->calculateOrderTotal(
            $id,
            $request->get('quantity')
        );

        return response()->json([
            'data' => $calculation
        ]);
    }
}
EOF

cat > app/Http/Controllers/Product/ProductColorController.php << 'EOF'
<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductColorService;
use App\Http\Requests\Product\CreateProductColorRequest;
use App\Http\Resources\Product\ProductColorResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductColorController extends Controller
{
    public function __construct(
        protected ProductColorService $colorService
    ) {}

    public function index(int $productId): JsonResponse
    {
        $colors = $this->colorService->getProductColors($productId);

        return response()->json([
            'data' => ProductColorResource::collection($colors)
        ]);
    }

    public function store(CreateProductColorRequest $request, int $productId): JsonResponse
    {
        $color = $this->colorService->create($productId, $request->validated());

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Cor adicionada com sucesso'
        ], 201);
    }

    public function update(Request $request, int $productId, int $colorId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'hex_code' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $color = $this->colorService->update($colorId, $validated);

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Cor atualizada com sucesso'
        ]);
    }

    public function destroy(int $productId, int $colorId): JsonResponse
    {
        $this->colorService->delete($colorId);

        return response()->json([
            'message' => 'Cor removida com sucesso'
        ]);
    }

    public function updateStock(Request $request, int $productId, int $colorId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $color = $this->colorService->updateStock($colorId, $request->get('quantity'));

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Estoque atualizado com sucesso'
        ]);
    }
}
EOF

cat > app/Http/Controllers/Product/ProductPrintAreaController.php << 'EOF'
<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductPrintAreaService;
use App\Http\Requests\Product\CreateProductPrintAreaRequest;
use App\Http\Resources\Product\ProductPrintAreaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductPrintAreaController extends Controller
{
    public function __construct(
        protected ProductPrintAreaService $printAreaService
    ) {}

    public function index(int $productId): JsonResponse
    {
        $areas = $this->printAreaService->getProductPrintAreas($productId);

        return response()->json([
            'data' => ProductPrintAreaResource::collection($areas)
        ]);
    }

    public function store(CreateProductPrintAreaRequest $request, int $productId): JsonResponse
    {
        $area = $this->printAreaService->create($productId, $request->validated());

        return response()->json([
            'data' => new ProductPrintAreaResource($area),
            'message' => 'Área de impressão adicionada com sucesso'
        ], 201);
    }

    public function update(Request $request, int $productId, int $areaId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'max_width_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'additional_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $area = $this->printAreaService->update($areaId, $validated);

        return response()->json([
            'data' => new ProductPrintAreaResource($area),
            'message' => 'Área de impressão atualizada com sucesso'
        ]);
    }

    public function destroy(int $productId, int $areaId): JsonResponse
    {
        $this->printAreaService->delete($areaId);

        return response()->json([
            'message' => 'Área de impressão removida com sucesso'
        ]);
    }
}
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔌 Creating Service Provider..."

cat > app/Providers/ProductServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use App\Repositories\Product\Eloquent\EloquentProductRepository;
use App\Repositories\Product\Eloquent\EloquentProductColorRepository;
use App\Repositories\Product\Eloquent\EloquentProductPrintAreaRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );

        $this->app->bind(
            ProductColorRepositoryInterface::class,
            EloquentProductColorRepository::class
        );

        $this->app->bind(
            ProductPrintAreaRepositoryInterface::class,
            EloquentProductPrintAreaRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
EOF

# ============================================
# ROUTES
# ============================================
echo "🛤️ Creating Routes..."

cat > routes/product.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductColorController;
use App\Http\Controllers\Product\ProductPrintAreaController;

/*
|--------------------------------------------------------------------------
| Product API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Product Module
|
*/

Route::prefix('v1')->group(function () {
    
    // Public product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/active', [ProductController::class, 'active']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/slug/{slug}', [ProductController::class, 'showBySlug']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/{id}/calculate-price', [ProductController::class, 'calculatePrice']);
        
        // Product colors
        Route::get('/{productId}/colors', [ProductColorController::class, 'index']);
        
        // Product print areas
        Route::get('/{productId}/print-areas', [ProductPrintAreaController::class, 'index']);
    });

    // Admin product routes (should be protected by auth middleware)
    Route::prefix('admin/products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/toggle-featured', [ProductController::class, 'toggleFeatured']);
        
        // Product colors management
        Route::post('/{productId}/colors', [ProductColorController::class, 'store']);
        Route::put('/{productId}/colors/{colorId}', [ProductColorController::class, 'update']);
        Route::delete('/{productId}/colors/{colorId}', [ProductColorController::class, 'destroy']);
        Route::patch('/{productId}/colors/{colorId}/stock', [ProductColorController::class, 'updateStock']);
        
        // Product print areas management
        Route::post('/{productId}/print-areas', [ProductPrintAreaController::class, 'store']);
        Route::put('/{productId}/print-areas/{areaId}', [ProductPrintAreaController::class, 'update']);
        Route::delete('/{productId}/print-areas/{areaId}', [ProductPrintAreaController::class, 'destroy']);
    });
});
EOF

# ============================================
# DATABASE MIGRATIONS
# ============================================
echo "🗄️ Creating Database Migrations..."

CURRENT_TIME=$(date +%s)
TIMESTAMP_1=$(date -d "@$((CURRENT_TIME + 1))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 1)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_2=$(date -d "@$((CURRENT_TIME + 2))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 2)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_3=$(date -d "@$((CURRENT_TIME + 3))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 3)) +%Y_%m_%d_%H%M%S)

cat > database/migrations/${TIMESTAMP_1}_create_products_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('min_quantity')->default(1);
            $table->string('image_url', 500)->nullable();
            $table->string('base_image_url', 500)->nullable();
            $table->text('design_hint')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index('sort_order');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_2}_create_product_colors_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name', 100);
            $table->string('hex_code', 7);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('stock_quantity')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('sort_order');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colors');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_3}_create_product_print_areas_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_print_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name', 100);
            $table->string('position');
            $table->text('description')->nullable();
            $table->decimal('max_width_cm', 8, 2)->nullable();
            $table->decimal('max_height_cm', 8, 2)->nullable();
            $table->decimal('additional_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('sort_order');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_print_areas');
    }
};
EOF

# ============================================
# FACTORIES
# ============================================
echo "🏭 Creating Factories..."

cat > database/factories/Product/ProductFactory.php << 'EOF'
<?php

namespace Database\Factories\Product;

use App\Models\Product\Product;
use App\Enums\Product\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Camiseta',
            'Caneca',
            'Cartão de Visita',
            'Pôster',
            'Camisa Polo',
            'Caderno de Anotações',
            'Garrafa de Água',
            'Sacola Ecológica',
        ]);

        return [
            'uuid' => Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomElement([250, 350, 450, 600, 750, 800, 1000, 1500]),
            'min_quantity' => $this->faker->randomElement([1, 5, 10, 20, 25, 50]),
            'image_url' => $this->faker->imageUrl(640, 480, 'product'),
            'base_image_url' => $this->faker->imageUrl(640, 480, 'product'),
            'design_hint' => $this->faker->sentence(15),
            'status' => ProductStatus::ACTIVE,
            'is_featured' => $this->faker->boolean(30),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::INACTIVE,
        ]);
    }

    public function tshirt(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Camiseta',
            'slug' => 'camiseta-' . Str::random(6),
            'price' => 1000,
            'min_quantity' => 1,
            'design_hint' => 'Design funciona melhor centralizado no peito, evite perto do colarinho/mangas',
        ]);
    }

    public function mug(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Caneca',
            'slug' => 'caneca-' . Str::random(6),
            'price' => 600,
            'min_quantity' => 1,
            'design_hint' => 'Padrões panorâmicos ou repetitivos funcionam bem',
        ]);
    }
}
EOF

cat > database/factories/Product/ProductColorFactory.php << 'EOF'
<?php

namespace Database\Factories\Product;

use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductColorFactory extends Factory
{
    protected $model = ProductColor::class;

    public function definition(): array
    {
        $colors = [
            ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
            ['name' => 'Preto', 'hex_code' => '#000000'],
            ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
            ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
            ['name' => 'Cinza', 'hex_code' => '#808080'],
            ['name' => 'Prata', 'hex_code' => '#C0C0C0'],
            ['name' => 'Natural', 'hex_code' => '#F5F5DC'],
            ['name' => 'Kraft', 'hex_code' => '#C4A35A'],
        ];

        $color = $this->faker->randomElement($colors);

        return [
            'name' => $color['name'],
            'hex_code' => $color['hex_code'],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'stock_quantity' => $this->faker->optional()->numberBetween(0, 1000),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }
}
EOF

cat > database/factories/Product/ProductPrintAreaFactory.php << 'EOF'
<?php

namespace Database\Factories\Product;

use App\Models\Product\ProductPrintArea;
use App\Enums\Product\PrintAreaPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPrintAreaFactory extends Factory
{
    protected $model = ProductPrintArea::class;

    public function definition(): array
    {
        $areas = [
            [
                'name' => 'Frente (Peito)',
                'position' => PrintAreaPosition::FRONT_CHEST,
                'max_width_cm' => 30,
                'max_height_cm' => 35,
            ],
            [
                'name' => 'Costas (Completo)',
                'position' => PrintAreaPosition::BACK_FULL,
                'max_width_cm' => 35,
                'max_height_cm' => 45,
            ],
            [
                'name' => 'Envolvente Completo',
                'position' => PrintAreaPosition::FULL_WRAP,
                'max_width_cm' => 25,
                'max_height_cm' => 10,
            ],
        ];

        $area = $this->faker->randomElement($areas);

        return [
            'name' => $area['name'],
            'position' => $area['position'],
            'description' => $this->faker->sentence(),
            'max_width_cm' => $area['max_width_cm'],
            'max_height_cm' => $area['max_height_cm'],
            'additional_price' => $this->faker->randomElement([0, 50, 100, 150]),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 5),
        ];
    }
}
EOF

# ============================================
# SEEDERS
# ============================================
echo "🌱 Creating Seeders..."

cat > database/seeders/ProductSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Enums\Product\PrintAreaPosition;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Camiseta',
                'slug' => 'camiseta',
                'description' => 'Camiseta personalizada de alta qualidade',
                'price' => 1000.00,
                'min_quantity' => 1,
                'design_hint' => 'Design funciona melhor centralizado no peito, evite perto do colarinho/mangas',
                'is_featured' => true,
                'sort_order' => 1,
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente (Peito)', 'position' => PrintAreaPosition::FRONT_CHEST, 'max_width_cm' => 30, 'max_height_cm' => 35],
                    ['name' => 'Costas (Completo)', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 35, 'max_height_cm' => 45],
                ],
            ],
            [
                'name' => 'Caneca',
                'slug' => 'caneca',
                'description' => 'Caneca de cerâmica para impressão sublimática',
                'price' => 600.00,
                'min_quantity' => 1,
                'design_hint' => 'Padrões panorâmicos ou repetitivos funcionam bem',
                'is_featured' => true,
                'sort_order' => 2,
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Envolvente Completo', 'position' => PrintAreaPosition::FULL_WRAP, 'max_width_cm' => 25, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Cartão de Visita',
                'slug' => 'cartao-de-visita',
                'description' => 'Cartões de visita profissionais impressos em papel de alta qualidade',
                'price' => 250.00,
                'min_quantity' => 50,
                'design_hint' => 'Layout profissional com texto legível, espaço para informações de contato',
                'is_featured' => false,
                'sort_order' => 3,
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 9, 'max_height_cm' => 5],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK, 'max_width_cm' => 9, 'max_height_cm' => 5],
                ],
            ],
            [
                'name' => 'Pôster',
                'slug' => 'poster',
                'description' => 'Pôster impresso em papel fosco de alta resolução',
                'price' => 800.00,
                'min_quantity' => 5,
                'design_hint' => 'Gráficos ousados, tipografia grande para visibilidade à distância',
                'is_featured' => true,
                'sort_order' => 4,
                'colors' => [
                    ['name' => 'Fosco Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Poster Completo', 'position' => PrintAreaPosition::FULL_POSTER, 'max_width_cm' => 60, 'max_height_cm' => 90],
                ],
            ],
            [
                'name' => 'Camisa Polo',
                'slug' => 'camisa-polo',
                'description' => 'Camisa polo profissional para uniformes corporativos',
                'price' => 1500.00,
                'min_quantity' => 10,
                'design_hint' => 'Logo/emblema pequeno, sutil e profissional',
                'is_featured' => false,
                'sort_order' => 5,
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Caderno de Anotações',
                'slug' => 'caderno-de-anotacoes',
                'description' => 'Caderno profissional com capa personalizada',
                'price' => 450.00,
                'min_quantity' => 25,
                'design_hint' => 'Designs centralizados, padrões de capa completa, ou arte de canto',
                'is_featured' => false,
                'sort_order' => 6,
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul', 'hex_code' => '#0000FF'],
                    ['name' => 'Kraft', 'hex_code' => '#C4A35A'],
                ],
                'print_areas' => [
                    ['name' => 'Capa Frontal', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 14, 'max_height_cm' => 21],
                ],
            ],
            [
                'name' => 'Garrafa de Água',
                'slug' => 'garrafa-de-agua',
                'description' => 'Garrafa de água reutilizável em alumínio',
                'price' => 750.00,
                'min_quantity' => 20,
                'design_hint' => 'Designs verticais ou padrões que envolvem a garrafa',
                'is_featured' => false,
                'sort_order' => 7,
                'colors' => [
                    ['name' => 'Prata', 'hex_code' => '#C0C0C0'],
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Corpo', 'position' => PrintAreaPosition::BODY, 'max_width_cm' => 20, 'max_height_cm' => 15],
                ],
            ],
            [
                'name' => 'Sacola Ecológica',
                'slug' => 'sacola-ecologica',
                'description' => 'Sacola reutilizável em algodão orgânico',
                'price' => 350.00,
                'min_quantity' => 50,
                'design_hint' => 'Gráficos grandes e centralizados visíveis quando carregada',
                'is_featured' => true,
                'sort_order' => 8,
                'colors' => [
                    ['name' => 'Natural', 'hex_code' => '#F5F5DC'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 25, 'max_height_cm' => 30],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $colors = $productData['colors'];
            $printAreas = $productData['print_areas'];
            unset($productData['colors'], $productData['print_areas']);

            $productData['uuid'] = Str::uuid();
            $product = Product::create($productData);

            foreach ($colors as $index => $colorData) {
                $colorData['product_id'] = $product->id;
                $colorData['sort_order'] = $index;
                $colorData['is_active'] = true;
                ProductColor::create($colorData);
            }

            foreach ($printAreas as $index => $areaData) {
                $areaData['product_id'] = $product->id;
                $areaData['sort_order'] = $index;
                $areaData['is_active'] = true;
                $areaData['additional_price'] = 0;
                ProductPrintArea::create($areaData);
            }
        }
    }
}
EOF

cat > database/seeders/ProductModuleSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
EOF

# ============================================
# FINAL SETUP INSTRUCTIONS
# ============================================
echo ""
echo "✅ Amazing Brindes Products Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 2 Enums (ProductStatus, PrintAreaPosition)"
echo "   - 3 Models (Product, ProductColor, ProductPrintArea)"
echo "   - 1 Trait (HasUuid)"
echo "   - 3 Repository Contracts"
echo "   - 3 Repository Implementations"
echo "   - 3 Services"
echo "   - 4 Form Requests"
echo "   - 4 API Resources"
echo "   - 3 Controllers"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 3 Migrations"
echo "   - 3 Factories"
echo "   - 2 Seeders"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Register the Service Provider in config/app.php:"
echo "      Add to 'providers' array:"
echo "      App\\Providers\\ProductServiceProvider::class,"
echo ""
echo "   2. Register the routes in routes/api.php:"
echo "      require __DIR__.'/product.php';"
echo ""
echo "   3. Run migrations:"
echo "      php artisan migrate"
echo ""
echo "   4. Seed the database with products:"
echo "      php artisan db:seed --class=ProductSeeder"
echo ""
echo "   5. Or run all seeders:"
echo "      php artisan db:seed --class=ProductModuleSeeder"
echo ""
echo "📋 API Endpoints Created:"
echo "   GET    /api/v1/products                    - List all products (paginated)"
echo "   GET    /api/v1/products/active             - List active products"
echo "   GET    /api/v1/products/featured           - List featured products"
echo "   GET    /api/v1/products/{id}               - Get product details"
echo "   GET    /api/v1/products/slug/{slug}        - Get product by slug"
echo "   POST   /api/v1/products/{id}/calculate-price - Calculate price"
echo "   GET    /api/v1/products/{id}/colors        - List product colors"
echo "   GET    /api/v1/products/{id}/print-areas   - List print areas"
echo ""
echo "   Admin endpoints (add auth middleware):"
echo "   POST   /api/v1/admin/products              - Create product"
echo "   PUT    /api/v1/admin/products/{id}         - Update product"
echo "   DELETE /api/v1/admin/products/{id}         - Delete product"
echo "   POST   /api/v1/admin/products/{id}/toggle-featured"
echo "   ... and more for colors and print areas"
echo ""
echo "🎉 Products Module ready for Amazing Brindes!"
