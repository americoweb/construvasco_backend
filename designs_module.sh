#!/bin/bash

# iOPS Amazing Brindes - Designs Module Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "🎨 Setting up Amazing Brindes Designs Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directory structure
echo "📁 Creating directory structure..."

mkdir -p app/Http/Controllers/Design
mkdir -p app/Services/Design
mkdir -p app/Repositories/Design/Contracts
mkdir -p app/Repositories/Design/Eloquent
mkdir -p app/Models/Design
mkdir -p app/Http/Requests/Design
mkdir -p app/Http/Resources/Design
mkdir -p app/Events/Design
mkdir -p app/Enums/Design
mkdir -p database/migrations
mkdir -p database/factories/Design
mkdir -p database/seeders
mkdir -p storage/app/public/designs
mkdir -p storage/app/public/logos
mkdir -p storage/app/public/references

echo "✅ Directory structure created!"

# ============================================
# ENUMS
# ============================================
echo "🏷️ Creating Enums..."

cat > app/Enums/Design/DesignStatus.php << 'EOF'
<?php

namespace App\Enums\Design;

enum DesignStatus: string
{
    case DRAFT = 'draft';
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFINED = 'refined';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Rascunho',
            self::GENERATING => 'Gerando',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::REFINED => 'Refinado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::GENERATING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFINED => 'purple',
        };
    }
}
EOF

# ============================================
# MODELS
# ============================================
echo "📦 Creating Models..."

cat > app/Models/Design/Design.php << 'EOF'
<?php

namespace App\Models\Design;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Enums\Design\DesignStatus;
use App\Traits\HasUuid;

class Design extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'product_id',
        'product_color_id',
        'product_print_area_id',
        'prompt',
        'mockup_url',
        'mockup_base64',
        'logo_path',
        'logo_mime_type',
        'reference_image_path',
        'reference_mime_type',
        'status',
        'generation_attempts',
        'ai_model_used',
        'ai_response_metadata',
        'is_from_suggestion',
        'suggestion_id',
    ];

    protected $casts = [
        'status' => DesignStatus::class,
        'generation_attempts' => 'integer',
        'ai_response_metadata' => 'array',
        'is_from_suggestion' => 'boolean',
    ];

    protected $hidden = [
        'mockup_base64',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function printArea(): BelongsTo
    {
        return $this->belongsTo(ProductPrintArea::class, 'product_print_area_id');
    }

    public function refinements(): HasMany
    {
        return $this->hasMany(DesignRefinement::class)->orderBy('created_at', 'desc');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', DesignStatus::COMPLETED);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isCompleted(): bool
    {
        return $this->status === DesignStatus::COMPLETED || $this->status === DesignStatus::REFINED;
    }

    public function hasMockup(): bool
    {
        return !empty($this->mockup_url) || !empty($this->mockup_base64);
    }

    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    public function hasReferenceImage(): bool
    {
        return !empty($this->reference_image_path);
    }

    public function getMockupAttribute(): ?string
    {
        if ($this->mockup_url) {
            return $this->mockup_url;
        }
        
        if ($this->mockup_base64) {
            return 'data:image/png;base64,' . $this->mockup_base64;
        }
        
        return null;
    }

    public function incrementGenerationAttempts(): void
    {
        $this->increment('generation_attempts');
    }
}
EOF

cat > app/Models/Design/DesignRefinement.php << 'EOF'
<?php

namespace App\Models\Design;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Design\DesignStatus;

class DesignRefinement extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_id',
        'refinement_prompt',
        'previous_mockup_url',
        'new_mockup_url',
        'new_mockup_base64',
        'status',
        'ai_response_metadata',
    ];

    protected $casts = [
        'status' => DesignStatus::class,
        'ai_response_metadata' => 'array',
    ];

    protected $hidden = [
        'new_mockup_base64',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function getNewMockupAttribute(): ?string
    {
        if ($this->new_mockup_url) {
            return $this->new_mockup_url;
        }
        
        if ($this->new_mockup_base64) {
            return 'data:image/png;base64,' . $this->new_mockup_base64;
        }
        
        return null;
    }
}
EOF

# ============================================
# REPOSITORY CONTRACTS
# ============================================
echo "📋 Creating Repository Contracts..."

cat > app/Repositories/Design/Contracts/DesignRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Design\Contracts;

use App\Models\Design\Design;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DesignRepositoryInterface
{
    public function findById(int $id): ?Design;
    
    public function findByUuid(string $uuid): ?Design;
    
    public function create(array $data): Design;
    
    public function update(int $id, array $data): Design;
    
    public function delete(int $id): bool;
    
    public function getBySession(string $sessionId): Collection;
    
    public function getByUser(int $userId): Collection;
    
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    public function getCompleted(): Collection;
    
    public function getWithRelations(int $id): ?Design;
}
EOF

cat > app/Repositories/Design/Contracts/DesignRefinementRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Design\Contracts;

use App\Models\Design\DesignRefinement;
use Illuminate\Database\Eloquent\Collection;

interface DesignRefinementRepositoryInterface
{
    public function findById(int $id): ?DesignRefinement;
    
    public function create(array $data): DesignRefinement;
    
    public function update(int $id, array $data): DesignRefinement;
    
    public function getByDesign(int $designId): Collection;
    
    public function getLatestByDesign(int $designId): ?DesignRefinement;
}
EOF

# ============================================
# REPOSITORY IMPLEMENTATIONS
# ============================================
echo "🗃️ Creating Repository Implementations..."

cat > app/Repositories/Design/Eloquent/EloquentDesignRepository.php << 'EOF'
<?php

namespace App\Repositories\Design\Eloquent;

use App\Models\Design\Design;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentDesignRepository implements DesignRepositoryInterface
{
    public function __construct(
        protected Design $model
    ) {}

    public function findById(int $id): ?Design
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Design
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): Design
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Design
    {
        $design = $this->findById($id);
        $design->update($data);
        return $design->fresh();
    }

    public function delete(int $id): bool
    {
        $design = $this->findById($id);
        return $design->delete();
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->model->bySession($sessionId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->byUser($userId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->byUser($userId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getCompleted(): Collection
    {
        return $this->model->completed()
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getWithRelations(int $id): ?Design
    {
        return $this->model->with(['product', 'color', 'printArea', 'refinements'])
            ->find($id);
    }
}
EOF

cat > app/Repositories/Design/Eloquent/EloquentDesignRefinementRepository.php << 'EOF'
<?php

namespace App\Repositories\Design\Eloquent;

use App\Models\Design\DesignRefinement;
use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDesignRefinementRepository implements DesignRefinementRepositoryInterface
{
    public function __construct(
        protected DesignRefinement $model
    ) {}

    public function findById(int $id): ?DesignRefinement
    {
        return $this->model->find($id);
    }

    public function create(array $data): DesignRefinement
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): DesignRefinement
    {
        $refinement = $this->findById($id);
        $refinement->update($data);
        return $refinement->fresh();
    }

    public function getByDesign(int $designId): Collection
    {
        return $this->model->where('design_id', $designId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestByDesign(int $designId): ?DesignRefinement
    {
        return $this->model->where('design_id', $designId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
EOF

# ============================================
# SERVICES
# ============================================
echo "⚙️ Creating Services..."

cat > app/Services/Design/DesignService.php << 'EOF'
<?php

namespace App\Services\Design;

use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Models\Design\Design;
use App\Enums\Design\DesignStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DesignService
{
    public function __construct(
        protected DesignRepositoryInterface $designRepository
    ) {}

    public function findById(int $id): Design
    {
        $design = $this->designRepository->findById($id);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function findByUuid(string $uuid): Design
    {
        $design = $this->designRepository->findByUuid($uuid);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function create(array $data): Design
    {
        if (!isset($data['status'])) {
            $data['status'] = DesignStatus::DRAFT;
        }
        
        if (!isset($data['generation_attempts'])) {
            $data['generation_attempts'] = 0;
        }
        
        return $this->designRepository->create($data);
    }

    public function update(int $id, array $data): Design
    {
        return $this->designRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $design = $this->findById($id);
        
        // Clean up files
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        return $this->designRepository->delete($id);
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->designRepository->getBySession($sessionId);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->designRepository->getByUser($userId);
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->designRepository->paginateByUser($userId, $perPage);
    }

    public function getDesignWithDetails(int $id): Design
    {
        $design = $this->designRepository->getWithRelations($id);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function uploadLogo(int $designId, UploadedFile $file): Design
    {
        $design = $this->findById($designId);
        
        // Delete old logo if exists
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        $path = $file->store('logos', 'public');
        
        return $this->designRepository->update($designId, [
            'logo_path' => $path,
            'logo_mime_type' => $file->getMimeType(),
        ]);
    }

    public function uploadReferenceImage(int $designId, UploadedFile $file): Design
    {
        $design = $this->findById($designId);
        
        // Delete old reference if exists
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        $path = $file->store('references', 'public');
        
        return $this->designRepository->update($designId, [
            'reference_image_path' => $path,
            'reference_mime_type' => $file->getMimeType(),
        ]);
    }

    public function removeLogo(int $designId): Design
    {
        $design = $this->findById($designId);
        
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        return $this->designRepository->update($designId, [
            'logo_path' => null,
            'logo_mime_type' => null,
        ]);
    }

    public function removeReferenceImage(int $designId): Design
    {
        $design = $this->findById($designId);
        
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        return $this->designRepository->update($designId, [
            'reference_image_path' => null,
            'reference_mime_type' => null,
        ]);
    }

    public function updateStatus(int $id, DesignStatus $status): Design
    {
        return $this->designRepository->update($id, ['status' => $status]);
    }

    public function saveMockup(int $id, string $mockupUrl = null, string $mockupBase64 = null): Design
    {
        $data = ['status' => DesignStatus::COMPLETED];
        
        if ($mockupUrl) {
            $data['mockup_url'] = $mockupUrl;
        }
        
        if ($mockupBase64) {
            $data['mockup_base64'] = $mockupBase64;
        }
        
        return $this->designRepository->update($id, $data);
    }

    public function markAsGenerating(int $id): Design
    {
        $design = $this->findById($id);
        $design->incrementGenerationAttempts();
        
        return $this->designRepository->update($id, [
            'status' => DesignStatus::GENERATING
        ]);
    }

    public function markAsFailed(int $id, array $metadata = []): Design
    {
        return $this->designRepository->update($id, [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => $metadata,
        ]);
    }

    public function getLogoAsBase64(int $designId): ?array
    {
        $design = $this->findById($designId);
        
        if (!$design->logo_path) {
            return null;
        }
        
        $content = Storage::disk('public')->get($design->logo_path);
        
        return [
            'data' => base64_encode($content),
            'mime_type' => $design->logo_mime_type,
        ];
    }

    public function getReferenceImageAsBase64(int $designId): ?array
    {
        $design = $this->findById($designId);
        
        if (!$design->reference_image_path) {
            return null;
        }
        
        $content = Storage::disk('public')->get($design->reference_image_path);
        
        return [
            'data' => base64_encode($content),
            'mime_type' => $design->reference_mime_type,
        ];
    }
}
EOF

cat > app/Services/Design/DesignRefinementService.php << 'EOF'
<?php

namespace App\Services\Design;

use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Models\Design\DesignRefinement;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Collection;

class DesignRefinementService
{
    public function __construct(
        protected DesignRefinementRepositoryInterface $refinementRepository,
        protected DesignRepositoryInterface $designRepository
    ) {}

    public function findById(int $id): DesignRefinement
    {
        $refinement = $this->refinementRepository->findById($id);
        
        if (!$refinement) {
            throw new \Exception('Refinamento não encontrado');
        }
        
        return $refinement;
    }

    public function createRefinement(int $designId, array $data): DesignRefinement
    {
        $design = $this->designRepository->findById($designId);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        $data['design_id'] = $designId;
        $data['previous_mockup_url'] = $design->mockup_url;
        
        if (!isset($data['status'])) {
            $data['status'] = DesignStatus::GENERATING;
        }
        
        $refinement = $this->refinementRepository->create($data);
        
        // Update parent design status
        $this->designRepository->update($designId, [
            'status' => DesignStatus::REFINED
        ]);
        
        return $refinement;
    }

    public function getDesignRefinements(int $designId): Collection
    {
        return $this->refinementRepository->getByDesign($designId);
    }

    public function getLatestRefinement(int $designId): ?DesignRefinement
    {
        return $this->refinementRepository->getLatestByDesign($designId);
    }

    public function saveRefinementMockup(int $id, string $mockupUrl = null, string $mockupBase64 = null): DesignRefinement
    {
        $data = ['status' => DesignStatus::COMPLETED];
        
        if ($mockupUrl) {
            $data['new_mockup_url'] = $mockupUrl;
        }
        
        if ($mockupBase64) {
            $data['new_mockup_base64'] = $mockupBase64;
        }
        
        $refinement = $this->refinementRepository->update($id, $data);
        
        // Also update the parent design mockup
        $this->designRepository->update($refinement->design_id, [
            'mockup_url' => $mockupUrl,
            'mockup_base64' => $mockupBase64,
        ]);
        
        return $refinement;
    }

    public function markAsFailed(int $id, array $metadata = []): DesignRefinement
    {
        return $this->refinementRepository->update($id, [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => $metadata,
        ]);
    }
}
EOF

# ============================================
# HTTP REQUESTS
# ============================================
echo "✅ Creating Form Requests..."

cat > app/Http/Requests/Design/CreateDesignRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class CreateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'product_print_area_id' => 'required|exists:product_print_areas,id',
            'prompt' => 'required_without:logo|nullable|string|max:2000',
            'session_id' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'is_from_suggestion' => 'nullable|boolean',
            'suggestion_id' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório',
            'product_id.exists' => 'Produto não encontrado',
            'product_color_id.required' => 'A cor é obrigatória',
            'product_color_id.exists' => 'Cor não encontrada',
            'product_print_area_id.required' => 'A área de impressão é obrigatória',
            'product_print_area_id.exists' => 'Área de impressão não encontrada',
            'prompt.required_without' => 'A descrição do design é obrigatória quando não há logo',
            'prompt.max' => 'A descrição não pode exceder 2000 caracteres',
        ];
    }
}
EOF

cat > app/Http/Requests/Design/UpdateDesignRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Design\DesignStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_color_id' => 'sometimes|exists:product_colors,id',
            'product_print_area_id' => 'sometimes|exists:product_print_areas,id',
            'prompt' => 'nullable|string|max:2000',
            'mockup_url' => 'nullable|url|max:1000',
            'status' => ['sometimes', new Enum(DesignStatus::class)],
        ];
    }
}
EOF

cat > app/Http/Requests/Design/UploadImageRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'A imagem é obrigatória',
            'image.file' => 'O arquivo deve ser uma imagem válida',
            'image.mimes' => 'A imagem deve ser PNG, JPG, JPEG ou WebP',
            'image.max' => 'A imagem não pode exceder 10MB',
        ];
    }
}
EOF

cat > app/Http/Requests/Design/CreateRefinementRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class CreateRefinementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refinement_prompt' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'refinement_prompt.required' => 'A descrição do refinamento é obrigatória',
            'refinement_prompt.max' => 'A descrição não pode exceder 2000 caracteres',
        ];
    }
}
EOF

# ============================================
# HTTP RESOURCES
# ============================================
echo "📤 Creating API Resources..."

cat > app/Http/Resources/Design/DesignResource.php << 'EOF'
<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductColorResource;
use App\Http\Resources\Product\ProductPrintAreaResource;
use Illuminate\Support\Facades\Storage;

class DesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'prompt' => $this->prompt,
            'mockup_url' => $this->mockup_url,
            'mockup' => $this->mockup,
            'has_logo' => $this->hasLogo(),
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,
            'has_reference_image' => $this->hasReferenceImage(),
            'reference_image_url' => $this->reference_image_path ? Storage::url($this->reference_image_path) : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_completed' => $this->isCompleted(),
            'has_mockup' => $this->hasMockup(),
            'generation_attempts' => $this->generation_attempts,
            'ai_model_used' => $this->ai_model_used,
            'is_from_suggestion' => $this->is_from_suggestion,
            'suggestion_id' => $this->suggestion_id,
            'product' => new ProductListResource($this->whenLoaded('product')),
            'color' => new ProductColorResource($this->whenLoaded('color')),
            'print_area' => new ProductPrintAreaResource($this->whenLoaded('printArea')),
            'refinements' => DesignRefinementResource::collection($this->whenLoaded('refinements')),
            'refinements_count' => $this->whenCounted('refinements'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Design/DesignListResource.php << 'EOF'
<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'prompt' => $this->prompt,
            'mockup' => $this->mockup,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_completed' => $this->isCompleted(),
            'product_name' => $this->product->name ?? null,
            'color_name' => $this->color->name ?? null,
            'print_area_name' => $this->printArea->name ?? null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Design/DesignRefinementResource.php << 'EOF'
<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignRefinementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refinement_prompt' => $this->refinement_prompt,
            'previous_mockup_url' => $this->previous_mockup_url,
            'new_mockup' => $this->new_mockup,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

# ============================================
# CONTROLLERS
# ============================================
echo "🎮 Creating Controllers..."

cat > app/Http/Controllers/Design/DesignController.php << 'EOF'
<?php

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Controller;
use App\Services\Design\DesignService;
use App\Http\Requests\Design\CreateDesignRequest;
use App\Http\Requests\Design\UpdateDesignRequest;
use App\Http\Requests\Design\UploadImageRequest;
use App\Http\Resources\Design\DesignResource;
use App\Http\Resources\Design\DesignListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DesignController extends Controller
{
    public function __construct(
        protected DesignService $designService
    ) {}

    public function store(CreateDesignRequest $request): JsonResponse
    {
        $design = $this->designService->create($request->validated());

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design criado com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $design = $this->designService->getDesignWithDetails($id);

        return response()->json([
            'data' => new DesignResource($design)
        ]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $design = $this->designService->findByUuid($uuid);
        $design->load(['product', 'color', 'printArea', 'refinements']);

        return response()->json([
            'data' => new DesignResource($design)
        ]);
    }

    public function update(UpdateDesignRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->update($id, $request->validated());

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design atualizado com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->designService->delete($id);

        return response()->json([
            'message' => 'Design excluído com sucesso'
        ]);
    }

    public function getBySession(string $sessionId): JsonResponse
    {
        $designs = $this->designService->getBySession($sessionId);

        return response()->json([
            'data' => DesignListResource::collection($designs)
        ]);
    }

    public function getByUser(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $designs = $this->designService->paginateByUser(
            $userId,
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => DesignListResource::collection($designs->items()),
            'meta' => [
                'current_page' => $designs->currentPage(),
                'last_page' => $designs->lastPage(),
                'per_page' => $designs->perPage(),
                'total' => $designs->total(),
            ]
        ]);
    }

    public function uploadLogo(UploadImageRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->uploadLogo($id, $request->file('image'));

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Logo carregado com sucesso'
        ]);
    }

    public function uploadReferenceImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->uploadReferenceImage($id, $request->file('image'));

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Imagem de referência carregada com sucesso'
        ]);
    }

    public function removeLogo(int $id): JsonResponse
    {
        $design = $this->designService->removeLogo($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Logo removido com sucesso'
        ]);
    }

    public function removeReferenceImage(int $id): JsonResponse
    {
        $design = $this->designService->removeReferenceImage($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Imagem de referência removida com sucesso'
        ]);
    }

    public function saveMockup(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'mockup_url' => 'nullable|url|max:1000',
            'mockup_base64' => 'nullable|string',
        ]);

        $design = $this->designService->saveMockup(
            $id,
            $request->get('mockup_url'),
            $request->get('mockup_base64')
        );

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Mockup salvo com sucesso'
        ]);
    }

    public function markAsGenerating(int $id): JsonResponse
    {
        $design = $this->designService->markAsGenerating($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design marcado como gerando'
        ]);
    }

    public function markAsFailed(Request $request, int $id): JsonResponse
    {
        $metadata = $request->get('metadata', []);
        $design = $this->designService->markAsFailed($id, $metadata);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design marcado como falha'
        ]);
    }

    public function getLogoBase64(int $id): JsonResponse
    {
        $logoData = $this->designService->getLogoAsBase64($id);

        if (!$logoData) {
            return response()->json([
                'message' => 'Logo não encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $logoData
        ]);
    }

    public function getReferenceImageBase64(int $id): JsonResponse
    {
        $imageData = $this->designService->getReferenceImageAsBase64($id);

        if (!$imageData) {
            return response()->json([
                'message' => 'Imagem de referência não encontrada'
            ], 404);
        }

        return response()->json([
            'data' => $imageData
        ]);
    }
}
EOF

cat > app/Http/Controllers/Design/DesignRefinementController.php << 'EOF'
<?php

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Controller;
use App\Services\Design\DesignRefinementService;
use App\Http\Requests\Design\CreateRefinementRequest;
use App\Http\Resources\Design\DesignRefinementResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DesignRefinementController extends Controller
{
    public function __construct(
        protected DesignRefinementService $refinementService
    ) {}

    public function index(int $designId): JsonResponse
    {
        $refinements = $this->refinementService->getDesignRefinements($designId);

        return response()->json([
            'data' => DesignRefinementResource::collection($refinements)
        ]);
    }

    public function store(CreateRefinementRequest $request, int $designId): JsonResponse
    {
        $refinement = $this->refinementService->createRefinement(
            $designId,
            $request->validated()
        );

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Refinamento criado com sucesso'
        ], 201);
    }

    public function latest(int $designId): JsonResponse
    {
        $refinement = $this->refinementService->getLatestRefinement($designId);

        if (!$refinement) {
            return response()->json([
                'message' => 'Nenhum refinamento encontrado'
            ], 404);
        }

        return response()->json([
            'data' => new DesignRefinementResource($refinement)
        ]);
    }

    public function saveMockup(Request $request, int $designId, int $refinementId): JsonResponse
    {
        $request->validate([
            'mockup_url' => 'nullable|url|max:1000',
            'mockup_base64' => 'nullable|string',
        ]);

        $refinement = $this->refinementService->saveRefinementMockup(
            $refinementId,
            $request->get('mockup_url'),
            $request->get('mockup_base64')
        );

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Mockup do refinamento salvo com sucesso'
        ]);
    }

    public function markAsFailed(Request $request, int $designId, int $refinementId): JsonResponse
    {
        $metadata = $request->get('metadata', []);
        $refinement = $this->refinementService->markAsFailed($refinementId, $metadata);

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Refinamento marcado como falha'
        ]);
    }
}
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔌 Creating Service Provider..."

cat > app/Providers/DesignServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use App\Repositories\Design\Eloquent\EloquentDesignRepository;
use App\Repositories\Design\Eloquent\EloquentDesignRefinementRepository;

class DesignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DesignRepositoryInterface::class,
            EloquentDesignRepository::class
        );

        $this->app->bind(
            DesignRefinementRepositoryInterface::class,
            EloquentDesignRefinementRepository::class
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

cat > routes/design.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Design\DesignController;
use App\Http\Controllers\Design\DesignRefinementController;

/*
|--------------------------------------------------------------------------
| Design API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Design Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('designs')->group(function () {
        // Create new design
        Route::post('/', [DesignController::class, 'store']);
        
        // Get design by ID
        Route::get('/{id}', [DesignController::class, 'show']);
        
        // Get design by UUID
        Route::get('/uuid/{uuid}', [DesignController::class, 'showByUuid']);
        
        // Update design
        Route::put('/{id}', [DesignController::class, 'update']);
        
        // Delete design
        Route::delete('/{id}', [DesignController::class, 'destroy']);
        
        // Get designs by session (for anonymous users)
        Route::get('/session/{sessionId}', [DesignController::class, 'getBySession']);
        
        // Upload logo
        Route::post('/{id}/logo', [DesignController::class, 'uploadLogo']);
        Route::delete('/{id}/logo', [DesignController::class, 'removeLogo']);
        Route::get('/{id}/logo/base64', [DesignController::class, 'getLogoBase64']);
        
        // Upload reference image
        Route::post('/{id}/reference-image', [DesignController::class, 'uploadReferenceImage']);
        Route::delete('/{id}/reference-image', [DesignController::class, 'removeReferenceImage']);
        Route::get('/{id}/reference-image/base64', [DesignController::class, 'getReferenceImageBase64']);
        
        // Mockup management
        Route::post('/{id}/mockup', [DesignController::class, 'saveMockup']);
        Route::post('/{id}/generating', [DesignController::class, 'markAsGenerating']);
        Route::post('/{id}/failed', [DesignController::class, 'markAsFailed']);
        
        // Refinements
        Route::get('/{designId}/refinements', [DesignRefinementController::class, 'index']);
        Route::post('/{designId}/refinements', [DesignRefinementController::class, 'store']);
        Route::get('/{designId}/refinements/latest', [DesignRefinementController::class, 'latest']);
        Route::post('/{designId}/refinements/{refinementId}/mockup', [DesignRefinementController::class, 'saveMockup']);
        Route::post('/{designId}/refinements/{refinementId}/failed', [DesignRefinementController::class, 'markAsFailed']);
    });

    // Authenticated user designs
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/designs', [DesignController::class, 'getByUser']);
    });
});
EOF

# ============================================
# DATABASE MIGRATIONS
# ============================================
echo "🗄️ Creating Database Migrations..."

CURRENT_TIME=$(date +%s)
TIMESTAMP_1=$(date -d "@$((CURRENT_TIME + 10))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 10)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_2=$(date -d "@$((CURRENT_TIME + 11))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 11)) +%Y_%m_%d_%H%M%S)

cat > database/migrations/${TIMESTAMP_1}_create_designs_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('product_print_area_id');
            $table->text('prompt')->nullable();
            $table->text('mockup_url')->nullable();
            $table->longText('mockup_base64')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('logo_mime_type', 50)->nullable();
            $table->string('reference_image_path', 500)->nullable();
            $table->string('reference_mime_type', 50)->nullable();
            $table->string('status')->default('draft');
            $table->integer('generation_attempts')->default(0);
            $table->string('ai_model_used')->nullable();
            $table->json('ai_response_metadata')->nullable();
            $table->boolean('is_from_suggestion')->default(false);
            $table->string('suggestion_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('status');
            $table->index('created_at');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('product_color_id')
                  ->references('id')
                  ->on('product_colors')
                  ->onDelete('cascade');

            $table->foreign('product_print_area_id')
                  ->references('id')
                  ->on('product_print_areas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_2}_create_design_refinements_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_refinements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_id');
            $table->text('refinement_prompt');
            $table->text('previous_mockup_url')->nullable();
            $table->text('new_mockup_url')->nullable();
            $table->longText('new_mockup_base64')->nullable();
            $table->string('status')->default('generating');
            $table->json('ai_response_metadata')->nullable();
            $table->timestamps();

            $table->index(['design_id', 'status']);
            $table->index('created_at');

            $table->foreign('design_id')
                  ->references('id')
                  ->on('designs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_refinements');
    }
};
EOF

# ============================================
# FACTORIES
# ============================================
echo "🏭 Creating Factories..."

cat > database/factories/Design/DesignFactory.php << 'EOF'
<?php

namespace Database\Factories\Design;

use App\Models\Design\Design;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DesignFactory extends Factory
{
    protected $model = Design::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first();
        
        return [
            'uuid' => Str::uuid(),
            'user_id' => null,
            'session_id' => Str::random(32),
            'product_id' => $product?->id ?? 1,
            'product_color_id' => $product?->colors()->first()?->id ?? 1,
            'product_print_area_id' => $product?->printAreas()->first()?->id ?? 1,
            'prompt' => $this->faker->sentence(10),
            'mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'mockup_base64' => null,
            'logo_path' => null,
            'logo_mime_type' => null,
            'reference_image_path' => null,
            'reference_mime_type' => null,
            'status' => DesignStatus::COMPLETED,
            'generation_attempts' => $this->faker->numberBetween(1, 3),
            'ai_model_used' => 'gemini-pro-vision',
            'ai_response_metadata' => null,
            'is_from_suggestion' => false,
            'suggestion_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::COMPLETED,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::DRAFT,
            'mockup_url' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => [
                'error' => 'Generation failed',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function fromSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_from_suggestion' => true,
            'suggestion_id' => Str::uuid(),
        ]);
    }

    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_path' => 'logos/' . Str::random(20) . '.png',
            'logo_mime_type' => 'image/png',
        ]);
    }
}
EOF

cat > database/factories/Design/DesignRefinementFactory.php << 'EOF'
<?php

namespace Database\Factories\Design;

use App\Models\Design\DesignRefinement;
use App\Models\Design\Design;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesignRefinementFactory extends Factory
{
    protected $model = DesignRefinement::class;

    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            'refinement_prompt' => $this->faker->sentence(8),
            'previous_mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'new_mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'new_mockup_base64' => null,
            'status' => DesignStatus::COMPLETED,
            'ai_response_metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::COMPLETED,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::FAILED,
            'new_mockup_url' => null,
        ]);
    }
}
EOF

# ============================================
# SEEDERS
# ============================================
echo "🌱 Creating Seeders..."

cat > database/seeders/DesignSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Design\Design;
use App\Models\Design\DesignRefinement;
use App\Models\Product\Product;
use App\Enums\Design\DesignStatus;
use Illuminate\Support\Str;

class DesignSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with(['colors', 'printAreas'])->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        $prompts = [
            'Logo minimalista com cores vibrantes para evento corporativo',
            'Design moderno com tipografia bold para lançamento de produto',
            'Arte abstrata com formas geométricas em azul e branco',
            'Ilustração de mascote divertido para equipe de vendas',
            'Pattern repetitivo com ícones de tecnologia',
            'Design elegante com bordas douradas para evento VIP',
            'Arte tropical com folhas e flores para festa de verão',
            'Logo esportivo com efeito 3D para time de futebol',
            'Design vintage com texturas de papel antigo',
            'Padrão floral delicado para produtos femininos',
        ];

        foreach ($products as $product) {
            if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                continue;
            }

            // Create 3-5 designs per product
            $numDesigns = rand(3, 5);
            
            for ($i = 0; $i < $numDesigns; $i++) {
                $design = Design::create([
                    'uuid' => Str::uuid(),
                    'user_id' => null,
                    'session_id' => 'seed_' . Str::random(20),
                    'product_id' => $product->id,
                    'product_color_id' => $product->colors->random()->id,
                    'product_print_area_id' => $product->printAreas->random()->id,
                    'prompt' => $prompts[array_rand($prompts)],
                    'mockup_url' => 'https://via.placeholder.com/640x480/cccccc/666666?text=' . urlencode($product->name),
                    'status' => DesignStatus::COMPLETED,
                    'generation_attempts' => rand(1, 3),
                    'ai_model_used' => 'gemini-pro-vision',
                    'is_from_suggestion' => (bool)rand(0, 1),
                    'suggestion_id' => rand(0, 1) ? Str::uuid() : null,
                ]);

                // Add refinements to some designs
                if (rand(0, 1)) {
                    $numRefinements = rand(1, 3);
                    
                    for ($j = 0; $j < $numRefinements; $j++) {
                        DesignRefinement::create([
                            'design_id' => $design->id,
                            'refinement_prompt' => 'Ajuste: ' . $prompts[array_rand($prompts)],
                            'previous_mockup_url' => $design->mockup_url,
                            'new_mockup_url' => 'https://via.placeholder.com/640x480/aaaaaa/444444?text=Refinement+' . ($j + 1),
                            'status' => DesignStatus::COMPLETED,
                        ]);
                    }

                    $design->update(['status' => DesignStatus::REFINED]);
                }
            }
        }

        // Create some failed designs
        for ($i = 0; $i < 3; $i++) {
            $product = $products->random();
            
            if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                continue;
            }

            Design::create([
                'uuid' => Str::uuid(),
                'user_id' => null,
                'session_id' => 'seed_failed_' . Str::random(10),
                'product_id' => $product->id,
                'product_color_id' => $product->colors->first()->id,
                'product_print_area_id' => $product->printAreas->first()->id,
                'prompt' => 'Design que falhou na geração',
                'mockup_url' => null,
                'status' => DesignStatus::FAILED,
                'generation_attempts' => 3,
                'ai_model_used' => 'gemini-pro-vision',
                'ai_response_metadata' => [
                    'error' => 'Generation failed after 3 attempts',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }
    }
}
EOF

cat > database/seeders/DesignModuleSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DesignModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DesignSeeder::class,
        ]);
    }
}
EOF

# ============================================
# FINAL OUTPUT
# ============================================
echo ""
echo "✅ Amazing Brindes Designs Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 1 Enum (DesignStatus)"
echo "   - 2 Models (Design, DesignRefinement)"
echo "   - 2 Repository Contracts"
echo "   - 2 Repository Implementations"
echo "   - 2 Services"
echo "   - 4 Form Requests"
echo "   - 3 API Resources"
echo "   - 2 Controllers"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 2 Migrations"
echo "   - 2 Factories"
echo "   - 2 Seeders"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Register the Service Provider in config/app.php:"
echo "      App\\Providers\\DesignServiceProvider::class,"
echo ""
echo "   2. Register the routes in routes/api.php:"
echo "      require __DIR__.'/design.php';"
echo ""
echo "   3. Run migrations:"
echo "      php artisan migrate"
echo ""
echo "   4. Create storage symlink (if not done):"
echo "      php artisan storage:link"
echo ""
echo "   5. Seed the database:"
echo "      php artisan db:seed --class=DesignSeeder"
echo ""
echo "📋 API Endpoints Created:"
echo "   POST   /api/v1/designs                    - Create design"
echo "   GET    /api/v1/designs/{id}               - Get design details"
echo "   GET    /api/v1/designs/uuid/{uuid}        - Get by UUID"
echo "   PUT    /api/v1/designs/{id}               - Update design"
echo "   DELETE /api/v1/designs/{id}               - Delete design"
echo "   GET    /api/v1/designs/session/{id}       - Get by session"
echo "   POST   /api/v1/designs/{id}/logo          - Upload logo"
echo "   POST   /api/v1/designs/{id}/reference-image - Upload reference"
echo "   POST   /api/v1/designs/{id}/mockup        - Save mockup"
echo "   POST   /api/v1/designs/{id}/generating    - Mark as generating"
echo "   POST   /api/v1/designs/{id}/refinements   - Create refinement"
echo "   GET    /api/v1/user/designs               - Get user designs (auth)"
echo ""
echo "🎉 Designs Module ready for Amazing Brindes!"
