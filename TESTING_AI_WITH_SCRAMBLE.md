# Testing AI Endpoints with Scramble

Scramble provides an interactive API documentation interface with a "Try It" feature that allows you to test endpoints directly from your browser.

## Accessing Scramble Documentation

1. **Start your Laravel development server:**
   ```bash
   php artisan serve
   ```

2. **Open the Scramble documentation in your browser:**
   ```
   http://localhost:8000/docs/api
   ```
   (Or use your configured APP_URL)

3. **Navigate to the AI endpoints:**
   - Look for the `v1/ai` section in the sidebar
   - You'll see all AI endpoints listed

## Testing AI Endpoints

### 1. Health Check (GET `/api/v1/ai/health`)

**Simplest test to verify AI service is configured:**

1. Click on the `GET /api/v1/ai/health` endpoint
2. Click the **"Try It"** button
3. Click **"Send Request"**
4. You should see:
   ```json
   {
     "status": "operational",
     "gemini_configured": true,
     "timestamp": "2025-01-16T10:00:00Z"
   }
   ```

### 2. Get Smart Suggestions (POST `/api/v1/ai/suggestions`)

**Test AI product suggestions:**

1. Click on `POST /api/v1/ai/suggestions`
2. Click **"Try It"**
3. Fill in the request body:

```json
{
  "goal": "Preciso de brindes para um evento corporativo de 50 pessoas. Quero algo elegante e profissional que represente nossa marca.",
  "budget": 5000,
  "include_bundles": true,
  "max_suggestions": 3
}
```

**With Logo (optional):**
```json
{
  "goal": "Brindes para evento corporativo",
  "budget": 5000,
  "logo_base64": "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
  "logo_mime_type": "image/png",
  "include_bundles": true
}
```

4. Click **"Send Request"**
5. You should receive product suggestions with mockups

### 3. Generate Mockup (POST `/api/v1/ai/mockup`)

**Test mockup generation for a specific product:**

1. Click on `POST /api/v1/ai/mockup`
2. Click **"Try It"**
3. Fill in the request body:

```json
{
  "product_id": 1,
  "design_prompt": "Um design minimalista com o logo da empresa no centro, cores corporativas azul e branco, estilo moderno e profissional",
  "logo_base64": "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
  "logo_mime_type": "image/png"
}
```

**With Reference Image:**
```json
{
  "product_id": 1,
  "design_prompt": "Aplicar este estilo de design no produto",
  "reference_image_base64": "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
  "reference_image_mime_type": "image/png",
  "color_id": 1,
  "print_area_id": 1
}
```

4. Click **"Send Request"**
5. You should receive a mockup URL

### 4. Refine Design (POST `/api/v1/ai/refine`)

**Test design refinement based on feedback:**

1. Click on `POST /api/v1/ai/refine`
2. Click **"Try It"**
3. Fill in the request body:

```json
{
  "current_prompt": "Um design minimalista com o logo da empresa no centro, cores corporativas azul e branco",
  "feedback": "Gostaria que o logo fosse maior e mais destacado, e adicionar um slogan abaixo do logo"
}
```

4. Click **"Send Request"**
5. You should receive a refined prompt

## Converting Images to Base64

To test with images, you need to convert them to base64. Here are some methods:

### Using JavaScript (Browser Console):
```javascript
// For a file input
const fileInput = document.querySelector('input[type="file"]');
const file = fileInput.files[0];
const reader = new FileReader();
reader.onloadend = () => {
  const base64 = reader.result.split(',')[1]; // Remove data:image/png;base64, prefix
  console.log(base64);
};
reader.readAsDataURL(file);
```

### Using PHP:
```php
$imagePath = 'path/to/image.png';
$imageData = file_get_contents($imagePath);
$base64 = base64_encode($imageData);
echo $base64;
```

### Using Online Tool:
- Visit: https://base64.guru/converter/encode/image
- Upload your image
- Copy the base64 string (without the `data:image/...;base64,` prefix)

## Tips for Testing

1. **Start with Health Check**: Always test the health endpoint first to ensure the API key is configured

2. **Use Simple Requests First**: Test without images first, then add images once basic functionality works

3. **Check Response Schema**: Scramble shows the expected response format - use it to understand what you'll receive

4. **Error Messages**: If you get errors, check:
   - Is `GEMINI_API_KEY` set in your `.env` file?
   - Are the required fields filled?
   - Are base64 images properly formatted?

5. **Base64 Format**: 
   - Remove the `data:image/png;base64,` prefix when sending
   - Only send the base64 string itself
   - Ensure images are not too large (recommended: under 1MB)

## Example Complete Test Flow

1. **Health Check** → Verify API is working
2. **Get Suggestions** (without images) → Get product recommendations
3. **Generate Mockup** (with a product_id from suggestions) → Create a visual mockup
4. **Refine Design** (using the design_prompt from mockup) → Improve the design

## Troubleshooting

### "Gemini not configured" error:
- Check your `.env` file has `GEMINI_API_KEY=your_key_here`
- Run `php artisan config:clear`

### "Invalid image format" error:
- Ensure base64 string doesn't include the `data:image/...;base64,` prefix
- Check mime_type matches the actual image format

### Timeout errors:
- Mockup generation can take 30-60 seconds
- Increase timeout in Scramble if needed
- Check your internet connection

## Next Steps

Once testing works in Scramble, you can:
- Integrate these endpoints into your frontend
- Use the same request/response format
- Handle errors appropriately based on the responses you see

