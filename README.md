# IT Hire Platform - Service Marketplace

A web-based service marketplace for IT professionals and clients in Uganda. The platform allows service clients to post IT service requests and service providers to browse, apply, and manage their profiles. Features include admin verification, escrow-based payments, and commission management.

## Features

### For Service Clients
- **Client Registration**: Create a client account to post service requests
- **Service Request Posting**: Post new IT service requests with budget, deadline, and description
- **Application Management**: View and manage applications from service providers
- **Escrow Payments**: Pay service budget upfront into escrow with verification fee
- **Verification**: Email and phone verification required to post requests

### For Service Providers
- **Provider Registration**: Create a profile as an IT service provider
- **Request Browsing**: Browse available service requests with search functionality
- **Service Applications**: Apply for requests and track application status
- **Qualifications**: Add and manage educational background and skills
- **Dashboard**: Personal dashboard to track applications and profile
- **Commission Acknowledgment**: 10% commission deducted from earnings

### For Admin
- **Admin Dashboard**: View platform statistics and recent activity
- **Verification Management**: Verify clients and providers via email and phone
- **Payment Release**: Release escrowed payments to providers with commission calculation
- **Service Requests Overview**: View and filter all service requests

## Payment Details

Clients can pay via:
- **Mobile Money:** MTN or Airtel
- **Bank Transfer:** Stanbic Bank or Equity Bank

Payment details (account numbers) are configured as static constants in `config.php`:
- Stanbic Bank: 1234567890
- MTN Mobile Money: 0766821496

These are displayed on the payment page for clients to see.

## Technology Stack

- **Backend**: PHP
- **Database**: MySQL (database: `it_hire`)
- **Frontend**: HTML, CSS, JavaScript
- **Server**: Apache (WAMP/XAMPP)

## Project Structure

```
project1/
├── admin/
│   ├── dashboard.php          # Admin dashboard with stats
│   ├── login.html             # Admin login form
│   ├── login_process.php      # Admin authentication
│   ├── payments.php           # Payment release management
│   ├── requests.php           # Service requests overview
│   └── verification.php       # Client/provider verification
├── assets/
│   └── style.css              # Main stylesheet
├── client/
│   ├── dashboard.php          # Client dashboard
│   ├── post_request.html      # Service request form
│   ├── post_request_process.php # Request posting processor
│   ├── register.html          # Client registration form
│   ├── register_process.php   # Client registration processor
│   └── view_applications.php  # View service applications
├── includes/
│   └── header.php             # Shared header for dynamic pages
├── payment/
│   ├── payment.php            # Payment form with static payment details
│   └── payment_process.php    # Payment processor
├── provider/
│   ├── add_qualification.html  # Qualification form
│   ├── add_qualification_process.php # Qualification processor
│   ├── dashboard.php          # Provider dashboard
│   ├── register.html          # Provider registration form
│   ├── register_process.php   # Provider registration processor
│   └── view_requests.php      # Browse service requests
├── config.php                 # Platform configuration constants
├── db_connect.php             # Database connection
├── index.php                  # Landing page
├── login.html                 # Login form
├── login_process.php          # Login processor
├── logout.php                 # Logout functionality
└── README.md                  # This file
```

## Database Schema

The application uses a MySQL database named `it_hire` with the following tables:

### Tables

1. **admin_company** - Admin user account
   - admin_id (PK)
   - username
   - password_hash

2. **service_client** - Client information
   - client_id (PK)
   - full_name
   - national_id
   - organization_name
   - email
   - phone_number
   - physical_address
   - industry
   - service_category_needed
   - verification_status
   - payment_verified
   - password_hash

3. **service_provider** - Provider profiles
   - provider_nin (PK)
   - full_name
   - email
   - phone_number
   - physical_address
   - specialization
   - experience_years
   - daily_rate
   - availability_status
   - commission_rate | DECIMAL(5,2) | Commission percentage (default 10.00) |
   - verification_fee | INT | Verification fee in UGX (default 50000) |
   - verification_status
   - password_hash

4. **qualification** - Provider qualifications
   - qualification_id (PK, auto-increment)
   - provider_nin (FK)
   - institution_name
   - qualification_title
   - qualification_level
   - specialization
   - skills_acquired
   - year_obtained
   - document_reference
   - verified_by_admin
   - date_added

5. **service_request** - Service requests
   - request_id (PK)
   - title
   - description
   - budget
   - deadline
   - category
   - date_posted
   - status
   - client_id (FK)

6. **application** - Service applications
   - application_id (PK)
   - application_date
   - status
   - request_id (FK)
   - provider_nin (FK)

7. **payment** - Payment records
   - payment_id (PK)
   - client_id (FK)
   - request_id (FK)
   - amount
   - currency
   - payment_method
   - payment_channel
   - transaction_reference
   - payment_status
   - payment_type
   - commission_amount
   - provider_nin (FK)
   - release_date
   - payment_date

8. **verification_log** - Verification history
   - log_id (PK, auto-increment)
   - user_type
   - user_id
   - verification_type
   - verified_by
   - verification_date
   - notes

## Installation

### Prerequisites

- WAMP Server or XAMPP installed
- MySQL database server running
- PHP 7.0 or higher

### Setup Instructions

1. **Clone or download the project** to your web server's root directory:
   ```
   C:\wamp64\www\project1\
   ```

2. **Create the database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `it_hire`
   - Run the migration script `migration.sql` to create all tables:
     - The script will drop old tables and create new ones
     - It includes the admin user with username `ITHIRE-ADMIN`
     - Admin password hash placeholder should be replaced with actual hash

3. **Configure database connection** (if needed):
   - Open `db_connect.php`
   - Verify the database credentials match your setup:
     ```php
     $host = 'localhost';
     $dbname = 'it_hire';
     $username = 'root';
     $password = '';
     ```

4. **Set admin password**:
   - Generate a bcrypt password hash for the admin user
   - Update the admin_company table with the new hash

5. **Start the server**:
   - Start WAMP/XAMPP services
   - Ensure Apache and MySQL are running

6. **Access the application**:
   - Open your browser and go to: `http://localhost/project1/`

## Usage

### For Admin

1. **Admin Login**:
   - Go to `http://localhost/project1/admin/login.html`
   - Enter admin username and password
   - Click "Login"

2. **Manage Verifications**:
   - Go to admin dashboard
   - Click "Verifications"
   - View clients and providers needing verification
   - Verify email and phone for each user

3. **Release Payments**:
   - Go to admin dashboard
   - Click "Payments"
   - View escrowed payments
   - Release payments to providers (10% commission deducted)

4. **View Requests**:
   - Go to admin dashboard
   - Click "Requests"
   - View and filter all service requests

### For Service Clients

1. **Register**:
   - Go to `http://localhost/project1/client/register.html`
   - Fill in client details (Client ID, Name, National ID, Email, Phone, Address, Password)
   - Submit the form

2. **Login**:
   - Go to `http://localhost/project1/login.html`
   - Select "Service Client" role
   - Enter email and password
   - Click "Login"

3. **Pay Verification Fee**:
   - Go to client dashboard
   - Click "Payments"
   - Pay UGX 50,000 verification fee
   - Wait for admin verification

4. **Post a Service Request**:
   - After verification and payment, go to client dashboard
   - Click "Post Request"
   - Fill in request details (Request ID, Title, Description, Budget, Deadline)
   - Submit the form

5. **Pay Service Budget**:
   - Go to client dashboard
   - Click "Payments"
   - Select "Pay for Service Request"
   - Enter request ID and budget
   - Total amount = Budget + UGX 50,000 verification fee

6. **View Applications**:
   - Go to client dashboard
   - Click "Applications" to view provider applications
   - Accept or reject applications

### For Service Providers

1. **Register**:
   - Go to `http://localhost/project1/provider/register.html`
   - Fill in personal details (NIN, Name, Email, Phone, Address, Specialization, Experience, Rate, Password)
   - Acknowledge 10% commission terms
   - Submit the form

2. **Login**:
   - Go to `http://localhost/project1/login.html`
   - Select "Service Provider" role
   - Enter email and password
   - Click "Login"

3. **Browse Requests**:
   - Go to `http://localhost/project1/provider/view_requests.php`
   - Browse available service requests
   - Use search to filter by title, skills, or client

4. **Apply for Requests**:
   - Login as a service provider
   - Browse requests and click "Apply Now" on any request
   - Application will be recorded

5. **Add Qualifications**:
   - Go to provider dashboard
   - Click "My Qualifications" or "+ Add Qualification"
   - Fill in academic details and submit

## Architecture

### Separation of Concerns

The project follows a clean separation between frontend (HTML) and backend (PHP):

- **HTML Files**: Pure HTML forms for user input with JavaScript for client-side validation and error display
- **PHP Processors**: Handle form submissions, validate data, interact with database, and redirect appropriately
- **Dynamic PHP Pages**: Render data from database for dashboards, listings, etc.

### Error Handling

All forms include client-side error handling:
- Validation errors are displayed via URL parameters
- JavaScript reads error parameters and shows user-friendly messages
- Database errors are caught and reported

### Session Management

- PHP sessions manage user authentication state
- Session variables track user type (client/provider/admin), ID, name, and initials
- Logout destroys session and redirects to login page

### Payment Flow

1. **Verification Fee**: UGX 50,000 one-time payment for clients
2. **Service Payment**: Budget + UGX 50,000 (verification fee) paid into escrow
3. **Payment Release**: Admin releases payment to provider with 10% commission deduction
4. **Commission**: 10% of payment amount deducted from provider's earnings

### Verification System

1. Clients and providers must be verified via email and phone
2. Admin verifies users and logs verification actions
3. Only fully verified clients can post service requests
4. Only fully verified providers can apply for requests

## Security Considerations

- Passwords are hashed using PHP's `password_hash()` function
- Prepared statements used for all database queries to prevent SQL injection
- Session validation on all protected pages
- Input validation on all form submissions
- Admin-only pages protected with session checks

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Ensure MySQL is running
   - Verify database name `it_hire` exists
   - Check credentials in `db_connect.php`

2. **Migration Script Errors**:
   - Ensure migration.sql is run in phpMyAdmin
   - Check for table conflicts (script drops old tables)
   - Verify admin password hash is set correctly

3. **Requests Not Appearing**:
   - Ensure deadline is a future date
   - Check if client is fully verified and payment_verified = 1
   - Verify request was successfully saved in database

4. **Login Not Working**:
   - Verify correct account type selected (Service Client vs Service Provider vs Admin)
   - Check email and password are correct
   - Ensure account was successfully registered

5. **Payment Issues**:
   - Ensure client is verified before posting requests
   - Check payment_verified flag in service_client table
   - Verify payment status in payment table

## Migration Notes

This platform was migrated from a job portal to a service marketplace. Key changes:

- **Terminology**: Company → Service Client, Job Seeker → Service Provider, Job → Service Request
- **Directories**: company/ → client/, seeker/ → provider/
- **Database**: 8 new tables with foreign key relationships
- **Features**: Added admin verification, escrow payments, commission management
- **Security**: Enhanced session management and role-based access control

## Development

### Adding New Features

To add new features:
1. Create HTML form in appropriate directory
2. Create PHP processor for form handling
3. Add database table if needed
4. Update navigation links in `includes/header.php`
5. Update admin dashboard if relevant

### Code Style

- Pure HTML for forms with inline JavaScript for error handling
- PHP processors for backend logic
- Dynamic PHP pages for data rendering
- Consistent naming: `form.html` and `form_process.php`

## License

This project is for educational purposes.

## Contact

For questions or support, please contact the development team.
