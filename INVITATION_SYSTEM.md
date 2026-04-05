# Tenant Invitation System

This system allows users to invite others to join their tenant/organization via email or SMS.

## How It Works

### 1. Creating Invitations
- Users can invite others by email or phone number
- System automatically detects the type (email/phone)
- Invitation is created with a unique token
- Email/SMS is sent with invitation link

### 2. Invitation Links

#### Email Invitations (with token)
```
http://localhost:4200/auth/register?invitation=TOKEN_HERE
```

#### SMS Invitations (with company name)
```
http://localhost:4200/auth/sign-up/COMPANY_NAME
```
Example: `http://localhost:4200/auth/sign-up/Above`

### 3. Registration with Invitation
When a user registers with an invitation, they are **automatically added to the existing tenant** that sent the invitation. **No new tenant is created.**

#### Option A: Using Invitation Token (Email)
```json
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "invitation_token": "abc123..."
}
```

#### Option B: Using Company Name (SMS)
```json
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "Above"
}
```

#### Option C: Automatic Detection
If no token or company name is provided, the system automatically checks for pending invitations by email:
```json
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Important**: When an invitation is accepted, the user joins the existing tenant. The `organization_name` field is **not required** and will be ignored.

### 4. Registration without Invitation
Only when registering without an invitation is a new tenant created:
```json
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "organization_name": "My New Organization"  // Required only for new tenants
}
```

### 5. Invitation Validation

#### Token-based validation (Email)
```
GET /api/auth/validate-invitation?token=abc123...
```

#### Company name validation (SMS)
```
GET /api/auth/validate-company?company_name=Above
```

Response:
```json
{
    "company": {
        "id": 1,
        "name": "Above",
        "slug": "above",
        "is_active": true,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

## Frontend Integration

### 1. Registration Page
The registration page should:
- Check for `invitation` parameter in URL (email) or company name in path (SMS)
- Validate the invitation token or company name
- Pre-fill the email field if invitation is valid
- Show invitation details (tenant name, inviter, role)
- **Hide the organization name field** when invitation is present
- Pass the invitation token or company name during registration

### 2. Example Registration Flow

#### Email Invitation Flow
```typescript
// 1. Check for invitation in URL
const urlParams = new URLSearchParams(window.location.search);
const invitationToken = urlParams.get('invitation');

if (invitationToken) {
    // 2. Validate invitation
    const response = await this.authService.validateInvitation(invitationToken);
    this.invitation = response.invitation;
    this.form.patchValue({ email: this.invitation.identifier });
    
    // 3. Hide organization name field (user will join existing tenant)
    this.showOrganizationField = false;
    this.invitationDetails = {
        tenantName: this.invitation.tenant.name,
        inviterName: this.invitation.inviter?.name,
        role: this.invitation.role
    };
}

// 4. Register with invitation
const registerData = {
    name: this.form.value.name,
    email: this.form.value.email,
    password: this.form.value.password,
    password_confirmation: this.form.value.password_confirmation,
    invitation_token: invitationToken // Include if available
};

await this.authService.register(registerData);
```

#### SMS Invitation Flow
```typescript
// 1. Check for company name in URL path
const pathSegments = window.location.pathname.split('/');
const companyName = pathSegments[pathSegments.length - 1]; // e.g., "Above"

if (companyName && companyName !== 'sign-up') {
    // 2. Validate company
    const response = await this.authService.validateCompany(companyName);
    this.company = response.company;
    
    // 3. Hide organization name field (user will join existing tenant)
    this.showOrganizationField = false;
    this.companyDetails = {
        name: this.company.name
    };
}

// 4. Register with company name
const registerData = {
    name: this.form.value.name,
    email: this.form.value.email,
    password: this.form.value.password,
    password_confirmation: this.form.value.password_confirmation,
    company_name: companyName // Include if available
};

await this.authService.register(registerData);
```

## Invitation States

- **pending**: Invitation sent, waiting for user to register
- **accepted**: User registered and joined the tenant
- **cancelled**: Invitation was cancelled by the inviter

## Email/SMS Templates

### Email Template
The system sends email invitations with:
- Inviter's name
- Tenant/organization name
- Role being offered
- Registration link with invitation token

### SMS Template
The system sends SMS invitations with:
- Inviter's name
- Tenant/organization name
- Registration link with company name

Example SMS: "You've been invited to join Above by Jane Smith. Register at: http://localhost:4200/auth/sign-up/Above"

## Security Features

- Invitation tokens are cryptographically secure
- Company names are validated against existing tenants
- Tokens expire when invitation is accepted or cancelled
- Users can only accept invitations for their email/phone
- Invitations are tied to specific tenants and roles
- **Invited users join existing tenants - no new tenants are created**

## Key Points

✅ **Multiple invitation methods** - Token-based (email) and company name (SMS)  
✅ **SMS-friendly URLs** - Short company names instead of long tokens  
✅ **Invited users join existing tenants** - No new tenant creation  
✅ **Automatic invitation acceptance** - No more pending invitations after signup  
✅ **Proper tenant assignment** - Users are correctly added to the inviting tenant  
✅ **Session management** - Current tenant is properly set after registration 