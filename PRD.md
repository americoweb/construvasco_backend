# Product Requirements Document (PRD)
## AI Print-on-Demand MVP Platform

**Version:** 1.0  
**Date:** 2024  
**Project Name:** That's Amazing - AI Print-on-Demand MVP  
**Status:** MVP/Development

---

## 1. Executive Summary

### 1.1 Product Overview
An AI-powered web application that enables users to design and order customized print-on-demand products (t-shirts, mugs, business cards, posters, etc.) using natural language prompts and AI-generated mockups. The platform leverages Google Gemini AI to create instant visual designs and provides an end-to-end e-commerce experience from design creation to order placement.

### 1.2 Target Market
- Small businesses and entrepreneurs in Mozambique (MT currency)
- Event organizers needing branded merchandise
- Individuals seeking custom-designed products
- Companies requiring promotional materials

### 1.3 Key Value Propositions
- **Instant AI Design Generation**: Create professional designs in minutes using text prompts
- **Smart Product Suggestions**: AI recommends products and bundles based on goals and budget
- **Visual Mockups**: Real-time preview of designs on actual products
- **Seamless Ordering**: Complete e-commerce flow from design to checkout
- **Design Refinement**: Iteratively improve designs with AI assistance

---

## 2. Product Goals & Objectives

### 2.1 Primary Goals
1. Enable users to create custom print-on-demand products without design skills
2. Reduce time-to-design from hours/days to minutes
3. Provide instant visual feedback through AI-generated mockups
4. Streamline the ordering process for customized products
5. Support multiple product categories with flexible customization options

### 2.2 Success Metrics
- User engagement: Time spent in customization flow
- Conversion rate: Designs created to orders placed
- User satisfaction: Design quality and ease of use
- Order volume: Number of completed orders
- Average order value: Revenue per transaction

---

## 3. User Personas

### 3.1 Primary Persona: Small Business Owner
- **Name**: Maria
- **Age**: 35-45
- **Location**: Mozambique
- **Goals**: Create branded merchandise for company events
- **Pain Points**: Limited design skills, tight budget, needs quick turnaround
- **Tech Savviness**: Moderate

### 3.2 Secondary Persona: Event Organizer
- **Name**: João
- **Age**: 25-40
- **Location**: Mozambique
- **Goals**: Order promotional items for events within budget
- **Pain Points**: Needs multiple products, wants cohesive branding
- **Tech Savviness**: High

---

## 4. Core Features & Requirements

### 4.1 Product Selection

#### 4.1.1 Product Catalog
**Description**: Users can browse and select from a catalog of customizable products.

**Products Available**:
1. **Camiseta (T-Shirt)**
   - Price: 1000 MT
   - Min Quantity: 1
   - Colors: White, Black, Navy Blue, Red
   - Print Areas: Front (Chest), Back (Full)
   - Design Hint: Design works best centered on chest, avoid near collar/sleeves

2. **Caneca (Mug)**
   - Price: 600 MT
   - Min Quantity: 1
   - Colors: White, Black
   - Print Areas: Full Wrap
   - Design Hint: Panoramic or repeating patterns work well

3. **Cartão de Visita (Business Card)**
   - Price: 250 MT
   - Min Quantity: 50
   - Colors: White
   - Print Areas: Front, Back
   - Design Hint: Professional layout with readable text, space for contact info

4. **Pôster (Poster)**
   - Price: 800 MT
   - Min Quantity: 5
   - Colors: Matte White
   - Print Areas: Full Poster
   - Design Hint: Bold graphics, large typography for distance visibility

5. **Camisa Polo (Polo Shirt)**
   - Price: 1500 MT
   - Min Quantity: 10
   - Colors: White, Black, Navy Blue, Gray
   - Print Areas: Front (Left Chest)
   - Design Hint: Small logo/emblem, subtle and professional

6. **Caderno de Anotações (Notebook)**
   - Price: 450 MT
   - Min Quantity: 25
   - Colors: Black, Blue, Kraft
   - Print Areas: Front Cover
   - Design Hint: Centered designs, full-cover patterns, or corner art

7. **Garrafa de Água (Water Bottle)**
   - Price: 750 MT
   - Min Quantity: 20
   - Colors: Silver, White, Black
   - Print Areas: Body
   - Design Hint: Vertical designs or patterns that wrap the bottle

8. **Sacola Ecológica (Eco Bag)**
   - Price: 350 MT
   - Min Quantity: 50
   - Colors: Natural, Black
   - Print Areas: Front
   - Design Hint: Large, centered graphics visible when carried

**Requirements**:
- Display products in a responsive grid layout
- Show product image, name, and price
- Click to select and proceed to customization
- Support mobile and desktop views

---

### 4.2 AI-Powered Smart Suggestions

#### 4.2.1 Goal-Based Product Recommendations
**Description**: Users provide a goal and budget, and AI suggests appropriate products with pre-generated mockups.

**User Inputs**:
- Goal/Objective (text): e.g., "brindes para evento de empresa"
- Budget (number): in MT (Mozambican Metical)
- Logo Upload (optional): User's logo file (PNG, JPEG, WebP)

**AI Processing**:
- Analyzes goal and budget constraints
- Selects products from catalog that fit budget
- Generates design prompts for each product
- Creates visual mockups using Gemini AI
- Returns structured suggestions with:
  - Single product suggestions
  - Bundle suggestions (2-5 products)
  - Estimated total prices
  - Pre-generated mockups
  - Design themes and color styles

**Output Format**:
```json
{
  "budget": number,
  "goal": string,
  "suggestions": [
    {
      "type": "single" | "bundle",
      "title": string,
      "products": [
        {
          "name": string,
          "design_prompt": string,
          "theme": string,
          "text_to_print": string,
          "color_style": string,
          "price": number,
          "mockupUrl": string
        }
      ],
      "estimated_total": number,
      "cta": string
    }
  ]
}
```

**Requirements**:
- Form validation for goal and budget
- Logo preview before submission
- Loading state during AI processing
- Error handling for API failures
- Display suggestions in organized cards
- Allow users to customize suggested products
- "Try Again" option to regenerate suggestions

---

### 4.3 Product Customization

#### 4.3.1 Design Customization Interface
**Description**: Users customize selected products with AI-generated designs.

**Customization Steps**:

1. **Color Selection**
   - Display available colors for the product
   - Visual color swatches with hex codes
   - Show selected color name
   - Update preview when color changes

2. **Print Area Selection**
   - Radio button selection for available print areas
   - Display area names (e.g., "Front (Chest)", "Back (Full)")
   - Visual indication of selected area

3. **Design Description (AI Prompt)**
   - Textarea for natural language design description
   - Placeholder examples
   - Character limit guidance
   - Required if no logo uploaded

4. **Image Uploads (Optional)**
   - **Logo Upload**: User's logo file
     - Accept: PNG, JPEG, WebP
     - Preview before submission
     - Remove option
   - **Reference Image Upload**: Design inspiration/reference
     - Accept: PNG, JPEG, WebP
     - Preview before submission
     - Remove option

5. **Generate Design Button**
   - Triggers AI mockup generation
   - Shows loading state
   - Disabled if no prompt and no logo
   - Error handling and display

**AI Mockup Generation Process**:
- Converts product base image to base64
- Processes logo and reference images if provided
- Sends to Gemini AI with structured prompt:
  - Product information
  - Color selection
  - User design prompt
  - Product-specific design hints
  - Logo integration instructions
  - Reference image guidance
- Returns base64-encoded mockup image
- Preserves product color and shape
- Applies design to product surface only

**Requirements**:
- Responsive two-column layout (product image + form)
- Sticky product image on desktop
- Real-time form validation
- Loading states during generation
- Error messages for failures
- Product-specific design hints displayed
- Support for multiple image uploads

---

### 4.4 Design Preview & Refinement

#### 4.4.1 Preview View
**Description**: Users review generated designs and can refine or add to cart.

**Display Elements**:
- Large mockup image preview
- Design details:
  - Product name
  - Selected color
  - Print area
  - Original prompt
  - Unit price
- Quantity selector (with minimum enforcement)
- Total price calculation

**Actions Available**:

1. **Improve Design**
   - Text input for improvement instructions
   - "Improve Design" button
   - Loading state during refinement
   - Updates mockup in place
   - Appends improvement to prompt history

2. **Add to Cart**
   - Quantity selection (minimum enforced)
   - Price calculation (unit price × quantity)
   - Adds design to cart
   - Navigates to cart view

3. **Download Options**:
   - **Download PNG**: Full mockup image
   - **Download Artwork**: Isolated design on transparent background
     - Uses AI to extract design from product
     - Returns PNG with transparency
     - Useful for printing

4. **Start Over**
   - Returns to customization view
   - Resets design state

**Requirements**:
- Responsive layout
- Sticky preview image on desktop
- Real-time quantity/price updates
- Download functionality
- Error handling for improvements
- Loading states for async operations

---

### 4.5 Shopping Cart

#### 4.5.1 Cart Management
**Description**: Users manage items before checkout.

**Features**:
- Display all cart items with:
  - Mockup thumbnail
  - Product name
  - Color and print area
  - Unit price
  - Quantity selector
  - Subtotal per item
  - Remove item button
- Order summary:
  - Subtotal
  - Shipping (to be calculated)
  - Total
- Actions:
  - Update quantities (minimum enforced)
  - Remove items
  - Continue shopping
  - Proceed to checkout

**Requirements**:
- Empty cart state with call-to-action
- Responsive grid layout
- Sticky order summary on desktop
- Real-time price calculations
- Quantity validation

---

### 4.6 Checkout Process

#### 4.6.1 Order Confirmation
**Description**: Users provide shipping information and confirm orders.

**Shipping Information Required**:
- Full Name (required)
- Address (required)
- WhatsApp Number (required, formatted)

**Order Summary Display**:
- List of items with thumbnails
- Quantity and unit prices
- Subtotal
- Total price

**Order Processing**:
- Generates unique order ID (alphanumeric, uppercase)
- Records order date
- Sets initial status: "Pendente" (Pending)
- Sends order confirmation email (simulated)
- Stores order in order history
- Clears cart
- Navigates to confirmation view

**Email Notification** (Simulated):
- Recipient: mikemiranda.m2@gmail.com
- Subject: "Confirmação de Novo Pedido: #[ORDER_ID]"
- Content includes:
  - Order ID, date, status
  - Total price
  - Itemized list with:
    - Product details
    - Color, print area
    - Quantity, prices
    - Design prompts
    - Mockup URLs
  - Shipping address
  - Contact information

**Requirements**:
- Form validation
- Responsive two-column layout
- Order summary display
- Error handling
- Success confirmation

---

### 4.7 Order Confirmation & History

#### 4.7.1 Confirmation View
**Description**: Displays order confirmation after successful checkout.

**Content**:
- Success indicator (checkmark icon)
- Thank you message
- Order ID (highlighted)
- Order summary:
  - Items with thumbnails
  - Product details
  - Quantities
  - Total price
- Shipping address display
- "Create Another Design" button

**Requirements**:
- Clear success indication
- Complete order details
- Easy navigation to new design

#### 4.7.2 Order History
**Description**: Users can view all past orders.

**Display**:
- List of all orders (newest first)
- For each order:
  - Order ID
  - Date
  - Status badge (color-coded):
    - Pendente (Pending): Gray
    - Em Produção (In Production): Yellow
    - Enviado (Shipped): Blue
    - Entregue (Delivered): Green
  - Total price
  - Item count
  - Items list with:
    - Mockup thumbnails
    - Product names
    - Color and print area
    - Quantities
    - Design prompts

**Empty State**:
- Message: "Nenhum design ainda!"
- Call-to-action: "Começar a Criar"

**Navigation**:
- Accessible from header "Meus Designs" link
- Badge showing order count
- "Create New Design" button

**Requirements**:
- Responsive card layout
- Status color coding
- Empty state handling
- Easy navigation

---

### 4.8 Navigation & Header

#### 4.8.1 Header Component
**Description**: Persistent navigation header across all views.

**Elements**:
- Logo (clickable, returns to product selection)
- Navigation links:
  - Home (product selection)
  - Products (product selection)
  - Templates (disabled, future feature)
  - Meus Designs (order history)
    - Badge showing order count
- Action buttons:
  - Search icon (placeholder)
  - Cart icon with item count badge
  - User icon (placeholder)

**Requirements**:
- Sticky positioning
- Responsive design (mobile menu)
- Badge notifications
- Smooth navigation

---

## 5. Technical Requirements

### 5.1 Technology Stack

**Frontend**:
- React 19.2.0
- TypeScript 5.8.2
- Vite 6.2.0 (build tool)
- Tailwind CSS (via className utilities)

**AI Integration**:
- Google Gemini AI (@google/genai 1.24.0)
  - Model: `gemini-2.5-flash-image` (image generation)
  - Model: `gemini-2.5-pro` (text/JSON generation)
  - Modalities: IMAGE, TEXT

**Environment**:
- Node.js (prerequisite)
- Environment variable: `GEMINI_API_KEY` (required)

### 5.2 API Integration

#### 5.2.1 Gemini AI Services

**generateMockup**:
- Input: Base product image, product name, color, prompt, design hints, optional logo/reference images
- Process: Multi-modal AI generation with image inputs
- Output: Base64-encoded mockup image (data URI)
- Error handling: User-friendly error messages

**improveMockup**:
- Input: Current mockup (base64), improvement prompt, product name, color
- Process: Refines existing design based on instructions
- Output: Updated base64-encoded mockup
- Constraint: Preserves product color and shape

**generateArtwork**:
- Input: Mockup image (base64), product name
- Process: Extracts design from product, creates transparent background
- Output: PNG with transparency (base64)
- Use case: Print-ready artwork download

**getSmartSuggestion**:
- Input: Goal (string), budget (number), optional logo
- Process:
  1. Text model generates structured JSON suggestions
  2. For each suggested product, generates mockup in parallel
  3. Returns complete suggestions with visual mockups
- Output: `SuggestionsResponse` with mockups
- Error handling: Fallback to base product images on failure

#### 5.2.2 Email Service

**sendOrderConfirmationEmail**:
- Current: Console logging (simulated)
- Future: Backend API integration
- Recipient: mikemiranda.m2@gmail.com
- Format: Structured order details

### 5.3 Data Models

**Product**:
```typescript
{
  id: number;
  name: string;
  imageUrl: string;
  baseImageUrl: string;
  colors: Color[];
  printAreas: PrintArea[];
  price?: number;
  minQuantity: number;
  designHint?: string;
}
```

**Design**:
```typescript
{
  product: Product;
  color: Color;
  printArea: PrintArea;
  prompt: string;
  mockupUrl: string;
  logoImage?: { data: string; mimeType: string; };
  referenceImage?: { data: string; mimeType: string; };
}
```

**CartItem** (extends Design):
```typescript
{
  id: string;
  quantity: number;
  // ... all Design properties
}
```

**Order**:
```typescript
{
  orderId: string;
  date: string;
  status: 'Pendente' | 'Em Produção' | 'Enviado' | 'Entregue';
  items: CartItem[];
  totalPrice: number;
  shippingAddress: {
    name: string;
    address: string;
    whatsapp: string;
  };
}
```

### 5.4 Application Flow

**State Management**:
- React useState hooks
- App-level state for:
  - Current step (AppStep enum)
  - Selected product
  - Current design
  - Cart items
  - Order history
  - Latest order

**Navigation Flow**:
1. Product Selection → Customization
2. Customization → Preview
3. Preview → Cart (or back to Customization)
4. Cart → Checkout
5. Checkout → Confirmation
6. Confirmation → Product Selection (new design)
7. Order History accessible from header

**Back Navigation**:
- Available in: Customization, Preview, Cart, Checkout
- Returns to previous step
- Preserves relevant state

---

## 6. User Experience Requirements

### 6.1 Design Principles
- **Modern & Clean**: Minimalist interface with focus on content
- **Responsive**: Mobile-first design, works on all screen sizes
- **Intuitive**: Clear navigation and action buttons
- **Visual Feedback**: Loading states, success indicators, error messages
- **Accessibility**: Semantic HTML, keyboard navigation support

### 6.2 UI Components
- Gradient buttons for primary actions
- Card-based layouts for content sections
- Sticky elements for better navigation
- Badge notifications for counts
- Icon system for visual cues
- Responsive grid layouts

### 6.3 Loading States
- Skeleton loaders or spinner animations
- Descriptive text: "A IA está a criar a sua obra-prima..."
- Disabled buttons during processing
- Progress indicators where applicable

### 6.4 Error Handling
- User-friendly error messages in Portuguese
- Validation feedback on forms
- Retry options for failed operations
- Fallback images for failed mockup generation

---

## 7. Business Rules

### 7.1 Pricing
- Prices displayed in MT (Mozambican Metical)
- Unit prices per product
- Minimum quantity requirements enforced
- Total = Unit Price × Quantity
- Shipping costs: "To be calculated" (future)

### 7.2 Order Processing
- Order IDs: Random alphanumeric, uppercase (9 characters)
- Initial status: "Pendente" (Pending)
- Order date: Current date (localized format)
- Email notification sent on order creation

### 7.3 Design Constraints
- Product colors and shapes cannot be changed by AI
- AI only modifies design applied to product surface
- Minimum quantities enforced per product type
- Design hints provided per product for guidance

### 7.4 File Uploads
- Accepted formats: PNG, JPEG, WebP
- Logo and reference images optional
- Preview before submission
- Base64 encoding for AI processing

---

## 8. Future Enhancements (Out of Scope for MVP)

### 8.1 Planned Features
- **Templates Library**: Pre-designed templates for quick customization
- **User Accounts**: Authentication and saved designs
- **Payment Integration**: Real payment processing
- **Shipping Calculator**: Real-time shipping cost calculation
- **Order Tracking**: Real-time order status updates
- **Design Collaboration**: Share designs for feedback
- **Bulk Ordering**: Simplified bulk order flow
- **Product Variations**: More color/print area options
- **Design History**: Save and reuse previous designs
- **Social Sharing**: Share designs on social media

### 8.2 Technical Improvements
- Backend API for order management
- Database for persistent storage
- Real email service integration
- Image optimization and CDN
- Analytics and tracking
- A/B testing framework
- Performance optimization
- SEO improvements

---

## 9. Success Criteria

### 9.1 MVP Launch Criteria
- [x] All core features implemented
- [x] AI integration working
- [x] Responsive design complete
- [x] Error handling in place
- [x] Order flow functional
- [ ] Backend API integration (future)
- [ ] Payment processing (future)
- [ ] Real email service (future)

### 9.2 User Acceptance Criteria
- Users can create designs using text prompts
- Users receive AI-generated mockups within reasonable time
- Users can refine designs iteratively
- Users can complete orders successfully
- Users can view order history
- Application works on mobile and desktop
- Error messages are clear and actionable

---

## 10. Risks & Mitigations

### 10.1 Technical Risks
- **AI API Failures**: Implement fallback images and retry logic
- **Image Loading Issues**: CORS proxy and direct fetch fallbacks
- **Performance**: Optimize image processing and lazy loading
- **Browser Compatibility**: Test across major browsers

### 10.2 Business Risks
- **AI Quality**: Monitor design quality and user feedback
- **Cost Management**: Track API usage and costs
- **Scalability**: Plan for backend infrastructure as needed

---

## 11. Glossary

- **Mockup**: AI-generated image showing design applied to product
- **Print Area**: Specific area of product where design is applied
- **Design Prompt**: User's text description of desired design
- **Artwork**: Isolated design extracted from product (transparent background)
- **Bundle**: Multiple products suggested together
- **MT**: Mozambican Metical (currency)

---

## 12. Appendix

### 12.1 File Structure
```
amazing_mvp/
├── components/
│   ├── CartView.tsx
│   ├── CheckoutView.tsx
│   ├── ConfirmationView.tsx
│   ├── CustomizationView.tsx
│   ├── Header.tsx
│   ├── OrderHistoryView.tsx
│   ├── PreviewView.tsx
│   ├── ProductSelector.tsx
│   ├── Loader.tsx
│   └── icons/
├── services/
│   ├── geminiService.ts
│   └── emailService.ts
├── App.tsx
├── index.tsx
├── types.ts
├── constants.ts
├── metadata.json
└── package.json
```

### 12.2 Environment Setup
1. Install dependencies: `npm install`
2. Set `GEMINI_API_KEY` in `.env.local`
3. Run development server: `npm run dev`
4. Build for production: `npm run build`

---

**Document Status**: Complete  
**Last Updated**: Based on current codebase analysis  
**Next Review**: Upon feature additions or major changes

