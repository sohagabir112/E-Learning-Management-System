# TMM Academy - Learning Management System

A comprehensive Learning Management System (LMS) built with PHP, MySQL, and modern web technologies. Features course management, user enrollment, progress tracking, reviews system, and complete Stripe payment integration.

![TMM Academy](https://img.shields.io/badge/TMM-Academy-blue?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-7.4+-orange?style=flat-square)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-blue?style=flat-square)
![Stripe](https://img.shields.io/badge/Stripe-Payments-635bff?style=flat-square)
![FontAwesome](https://img.shields.io/badge/FontAwesome-6.4+-339af0?style=flat-square)

## 🌟 Features

### 📚 Course Management
- **Comprehensive Course Catalog**: 8+ courses across multiple programming categories
- **Structured Learning**: Modules and video lessons with organized content
- **Instructor Profiles**: Dedicated instructor management with expertise tracking
- **Course Categories**: Organized categories (Python, Web Development, JavaScript, etc.)
- **Content Management**: Video lessons with progress tracking capabilities

### 👥 User Management
- **Secure Authentication**: User registration and login with session management
- **Profile Management**: Customizable user profiles with avatar uploads
- **Progress Tracking**: Detailed learning analytics and completion monitoring
- **Wishlist System**: Save and manage favorite courses
- **Activity Logging**: Comprehensive user action tracking

### ⭐ Reviews & Feedback
- **Rating System**: 5-star rating system for courses
- **Review Management**: User-generated reviews with moderation
- **Dynamic Display**: Homepage integration with recent reviews
- **Review Analytics**: Review statistics and course reputation

### 💳 Payment Integration
- **Stripe Payment Processing**: Complete PCI-compliant payment system
- **Multiple Payment Methods**: Support for cards, digital wallets
- **Secure Transactions**: Encrypted payment processing with webhooks
- **Enrollment Management**: Automatic course access upon successful payment
- **Transaction Logging**: Complete audit trail for all payments

### 🎨 Modern UI/UX
- **Responsive Design**: Mobile-first approach with adaptive layouts
- **Custom CSS Framework**: Modern styling with CSS Grid and Flexbox
- **Interactive Elements**: Dynamic forms, progress bars, and navigation
- **Typography**: Google Fonts (Poppins & Roboto) for professional appearance
- **Accessibility**: WCAG compliant with semantic HTML

### ⚙️ Admin Panel
- **Dashboard Analytics**: User statistics, enrollment tracking, revenue reports
- **User Management**: View, edit, and manage user accounts
- **Course Administration**: Activate/deactivate courses, manage content
- **Review Moderation**: Approve/reject user reviews
- **Category Management**: Organize and maintain course categories

## 🚀 Quick Start

### Prerequisites
- **PHP 7.4+** with MySQL extension and cURL support
- **MySQL 5.7+** or **MariaDB 10.0+**
- **Apache/Nginx** web server with mod_rewrite
- **Composer** (required for Stripe SDK dependency)
- **SSL Certificate** (recommended for payment processing)

### Installation

1. **Clone or Download**
   ```bash
   git clone https://github.com/your-username/tmm-academy.git
   # or download ZIP and extract to your web directory
   cd tmm-academy
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   ```bash
   # Option 1: One-click setup (Recommended)
   # Open in browser: http://localhost/tmm-academy/database/run_reviews_sql.php

   # Option 2: Manual import
   mysql -u root -p < database/tmm_academy.sql

   # Option 3: phpMyAdmin
   # 1. Create database: tmm_academy
   # 2. Import: database/tmm_academy.sql
   ```

4. **Web Server Configuration**
   - Point your web server document root to `tmm-academy/` directory
   - Ensure `uploads/` and `uploads/profiles/` directories are writable:
     ```bash
     chmod 755 uploads/
     chmod 755 uploads/profiles/
     ```
   - Enable URL rewriting if using Apache with `.htaccess`

5. **Access the Application**
   ```
   Main Site: http://localhost/tmm-academy/
   Admin Panel: http://localhost/tmm-academy/admin/
   Database Setup: http://localhost/tmm-academy/database/run_reviews_sql.php
   ```

## 📁 Project Structure

tmm-academy/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom CSS framework
│   ├── js/
│   │   └── main.js            # Frontend JavaScript
│   └── images/
│       ├── courses/           # Course thumbnails
│       ├── icons/             # UI icons
│       └── instructors/       # Instructor photos
├── database/
│   ├── tmm_academy.sql        # Complete database schema
│   ├── run_reviews_sql.php    # One-click database setup
│   └── README_reviews.md      # Database setup guide
├── includes/
│   ├── config.php             # Configuration & Stripe setup
│   ├── auth.php               # Authentication functions
│   ├── header.php             # HTML header template
│   ├── footer.php             # HTML footer template
│   └── functions.php          # Utility functions
├── user/
│   ├── dashboard.php          # User dashboard
│   ├── my-courses.php         # Enrolled courses overview
│   ├── learning.php           # Course learning interface
│   ├── payment.php            # Stripe payment processing
│   ├── payment-success.php    # Payment confirmation
│   ├── payment-cancel.php     # Payment failure/cancellation
│   ├── profile.php            # Profile management
│   ├── wishlist.php           # Course wishlist
│   ├── wishlist-api.php       # Wishlist AJAX endpoints
│   ├── enroll.php             # Course enrollment
│   ├── review.php             # Review submission
│   └── update-progress.php    # Progress tracking API
├── admin/
│   ├── dashboard.php          # Admin dashboard
│   ├── index.php              # Admin login
│   └── logout.php             # Admin logout
├── uploads/
│   └── profiles/              # User profile images
├── composer.json              # PHP dependencies
├── PAYMENT_SETUP.md           # Payment integration guide
├── courses.php                # Course catalog
├── course-detail.php          # Individual course pages
├── index.php                  # Homepage with reviews
├── login.php                  # User authentication
├── register.php               # User registration
├── logout.php                 # User logout
├── faq.php                    # FAQ page
├── search.php                 # Course search functionality
├── demo-payment.php           # Payment demo (legacy)
├── setup-stripe.php           # Stripe configuration utility
├── test-stripe.php            # Stripe testing utility
├── stripe-webhook.php         # Stripe webhook handler
├── default-avatar.php         # Default avatar generator
└── README.md                  # This documentation
```

## 🗄️ Database Schema

### Core Tables (5 tables)

#### Users (`users`)
- User accounts with authentication and profile management

#### Admins (`admins`)
- Separate admin authentication system for panel access

#### Courses (`courses`)
- Complete course catalog with pricing and metadata

#### Categories (`categories`)
- Course organization and categorization system

#### Instructors (`instructors`)
- Instructor profiles and specialization tracking

### Learning Management (5 tables)

#### Modules (`modules`)
- Course content organization into structured modules

#### Videos (`videos`)
- Individual video lessons with metadata

#### Enrollments (`enrollments`)
- Course enrollment tracking with payment status

#### User Progress (`user_progress`)
- Detailed learning progress and completion tracking

#### Wishlists (`wishlists`)
- User course wishlist functionality

### Reviews & Activity (4 tables)

#### Reviews (`reviews`)
- User-generated course reviews and ratings

#### FAQs (`faqs`)
- Frequently asked questions management

#### Payment Logs (`payment_logs`)
- Complete transaction history and audit trail

#### User Activity Logs (`user_activity_logs`)
- Comprehensive user action tracking

### Key Relationships
- **Users** enroll in **Courses** → **Enrollments**
- **Courses** belong to **Categories** and have **Instructors**
- **Courses** contain **Modules** with **Videos**
- **Users** track progress on **Videos** → **User Progress**
- **Users** can review **Courses** → **Reviews**
- All payments logged in **Payment Logs**
- User actions tracked in **Activity Logs**

## 💳 Payment Integration

### Stripe Payment Processing
Complete PCI-compliant payment processing with Stripe integration:

#### Features Implemented
- ✅ **Secure Payment Processing**: PCI-compliant Stripe integration
- ✅ **Multiple Payment Methods**: Cards, digital wallets, and local payment methods
- ✅ **Real-time Processing**: Instant payment confirmation and enrollment
- ✅ **Webhook Support**: Automated payment status updates
- ✅ **Error Handling**: Comprehensive error management and user feedback
- ✅ **Transaction Logging**: Complete audit trail for compliance
- ✅ **Refund Management**: Built-in refund tracking capabilities

#### Test Environment
```bash
# Test API Keys (automatically configured)
Publishable Key: pk_test_...
Secret Key: sk_test_...
```

#### Test Cards for Development
| Card Number | Description |
|-------------|-------------|
| 4242424242424242 | Succeeds |
| 4000000000000002 | Declines |
| 4000000000009995 | Insufficient funds |
| 4000000000009987 | Expired card |

#### Production Setup
1. **Get Stripe Account**: Sign up at [stripe.com](https://stripe.com)
2. **Obtain API Keys**: Get live publishable and secret keys
3. **Configure Webhooks**: Set up webhook endpoints for payment events
4. **SSL Certificate**: Required for live payment processing
5. **Domain Verification**: Complete Stripe's activation checklist

#### Security Features
- **Tokenization**: Card details never touch your server
- **SSL Encryption**: All payment data encrypted in transit
- **PCI Compliance**: Meets PCI DSS requirements
- **Webhook Verification**: Secure webhook signature validation
- **Audit Logging**: Complete transaction history

## 🔐 Security Features

- **Password Hashing**: bcrypt password encryption
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and validation
- **CSRF Protection**: Session-based tokens
- **Secure File Uploads**: Type and size validation
- **Session Management**: Secure PHP sessions with auto-expiry
- **Payment Security**: No card details stored on server
- **Audit Logging**: Complete payment transaction logging

## ⚡ Performance Optimizations

### Database Indexes
- **Composite Indexes**: Optimized for common query patterns
- **Foreign Key Indexes**: Improved join performance
- **Status Indexes**: Fast filtering by payment/enrollment status
- **Date Indexes**: Efficient time-based queries
- **Unique Constraints**: Data integrity enforcement

### Query Optimizations
- **Prepared Statements**: Reduced SQL injection risk and improved performance
- **Efficient Joins**: Optimized table relationships
- **Selective Loading**: Load only required data
- **Caching Ready**: Prepared for Redis/memcached integration

### Advanced Features
- **Progress Tracking**: Video progress with resume functionality
- **Payment Analytics**: Transaction history and revenue reporting
- **Refund Management**: Complete refund tracking system
- **User Activity Logs**: Comprehensive behavior analytics
- **Wishlist Management**: AJAX-powered course bookmarking
- **Review System**: Dynamic review display with moderation
- **Search Functionality**: Course search and filtering
- **FAQ System**: Dynamic FAQ management

## 🎨 Design & Styling

### Frontend Technologies
- **Custom CSS Framework**: Modern, responsive design system
- **Font Awesome 6.4+**: Comprehensive icon library
- **Google Fonts**: Poppins (headings) and Roboto (body) typefaces
- **CSS Grid & Flexbox**: Advanced layout techniques
- **Mobile-First Design**: Responsive across all devices

### Color Palette
```css
Primary: #2C3E50    /* Dark Blue-Gray */
Secondary: #8E44AD  /* Purple */
Accent: #27AE60     /* Green */
Warning: #E67E22    /* Orange */
Danger: #E74C3C    /* Red */
Light: #F8F9FA     /* Light Gray */
Dark: #343A40      /* Dark Gray */
Border: #DEE2E6    /* Light Border */
```

### Design Features
- **CSS Variables**: Consistent theming and easy customization
- **Shadows & Depth**: Layered design with multiple shadow levels
- **Typography Scale**: Hierarchical text sizing and spacing
- **Component Library**: Reusable UI components
- **Interactive States**: Hover, focus, and active state styling

## 📱 Responsive Design

- **Mobile-First Approach**: Optimized for smartphones and tablets
- **Adaptive Layouts**: CSS Grid and Flexbox for all screen sizes
- **Touch Interactions**: Mobile-friendly navigation and forms
- **Progressive Enhancement**: Enhanced features for larger screens
- **Cross-Browser**: Compatible with modern browsers

## 📦 Dependencies & Libraries

### PHP Dependencies (Composer)
```json
{
  "php": ">=7.4",
  "stripe/stripe-php": "^10.0"
}
```

### Frontend Libraries
- **Font Awesome 6.4+**: Icon library via CDN
- **Google Fonts**: Poppins & Roboto typefaces
- **Vanilla JavaScript**: No framework dependencies

### Server Requirements
- **PHP 7.4+** with extensions: mysqli, curl, mbstring
- **MySQL 5.7+** or **MariaDB 10.0+**
- **Apache/Nginx** with URL rewriting
- **SSL Certificate** for payment processing

## 🔧 Development & Testing Tools

### Setup Utilities
- **`database/run_reviews_sql.php`**: One-click database setup with sample data
- **`setup-stripe.php`**: Interactive Stripe API key configuration
- **`test-stripe.php`**: Stripe API connectivity testing tool

### Payment Testing
- **`demo-payment.php`**: Legacy payment form demo
- **Stripe Test Cards**: Comprehensive test card numbers for development
- **Webhook Testing**: Local webhook endpoint testing

### Database Management
- **Complete SQL Schema**: `database/tmm_academy.sql`
- **Sample Data**: Pre-populated with users, courses, and reviews
- **phpMyAdmin Compatible**: Manual database import support

### Debugging Configuration
```php
// Enable development error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database debugging
define('DB_DEBUG', true);

// Payment debugging
define('STRIPE_DEBUG', true);
```

## 🚀 Deployment & Production

### Pre-deployment Setup
1. **Environment Configuration**
   ```bash
   # Update config.php with production values
   define('DB_HOST', 'your_production_db_host');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_secure_password');
   define('SITE_URL', 'https://yourdomain.com');
   ```

2. **Database Setup**
   ```bash
   # Import production database
   mysql -u username -p production_db < database/tmm_academy.sql
   ```

3. **File Permissions**
   ```bash
   # Set proper permissions for web server
   chown -R www-data:www-data /var/www/tmm-academy/
   chmod -R 755 /var/www/tmm-academy/
   chmod -R 777 /var/www/tmm-academy/uploads/
   ```

### Production Checklist
- [ ] ✅ Configure production database credentials
- [ ] ✅ Set up Stripe live API keys
- [ ] ✅ Install SSL certificate (required for payments)
- [ ] ✅ Configure domain and DNS settings
- [ ] ✅ Set up automated backups
- [ ] ✅ Configure error logging and monitoring
- [ ] ✅ Test all payment flows
- [ ] ✅ Verify email functionality (if implemented)
- [ ] ✅ Set up cron jobs for maintenance
- [ ] ✅ Configure firewall and security settings

### Sample Data & Testing Accounts
After database setup, the following test accounts are available:

**Admin Access:**
- URL: `https://yourdomain.com/admin/`
- Username: `admin`
- Password: `admin123`

**Sample Users:**
- Multiple user accounts with enrolled courses
- Pre-populated reviews and ratings
- Sample payment transactions

### Environment Variables (Recommended)
```bash
# .env file or server environment
DB_HOST=production-db-host
DB_USER=secure-db-user
DB_PASS=strong-password
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
SITE_URL=https://yourdomain.com
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🔌 API Endpoints

### AJAX Endpoints
- **`user/wishlist-api.php`**: Wishlist add/remove operations
- **`user/update-progress.php`**: Progress tracking updates
- **`stripe-webhook.php`**: Payment webhook handler

### Authentication Endpoints
- **`login.php`**: User authentication
- **`register.php`**: User registration
- **`logout.php`**: Session termination
- **`admin/index.php`**: Admin authentication

### Admin Panel
- **`admin/dashboard.php`**: Administrative dashboard
- Complete user, course, and review management interfaces

## 📞 Support & Resources

### Documentation
- **Setup Guide**: `PAYMENT_SETUP.md` for Stripe integration
- **Database Guide**: `database/README_reviews.md` for schema details
- **Inline Comments**: Comprehensive code documentation

### Getting Help
- **GitHub Issues**: Bug reports and feature requests
- **Code Review**: Inline documentation and comments
- **Configuration Files**: Detailed setup instructions

### Security Notes
- Store API keys securely (never in version control)
- Use environment variables in production
- Keep webhook secrets confidential
- Regularly update PHP and dependencies

## 🔄 Version History

### v1.0.0 (Current Release)
- ✅ **Complete LMS Platform**: Full learning management system
- ✅ **User Management**: Registration, authentication, profiles
- ✅ **Course Management**: Catalog, enrollment, progress tracking
- ✅ **Review System**: User ratings and feedback management
- ✅ **Payment Integration**: Stripe payment processing
- ✅ **Admin Panel**: Complete administrative dashboard
- ✅ **Responsive Design**: Mobile-first, modern UI/UX
- ✅ **Database Schema**: 14-table comprehensive structure
- ✅ **Security Features**: Authentication, CSRF protection, input validation
- ✅ **Activity Logging**: User action tracking and analytics

### Recent Updates
- 🔄 **Stripe Integration**: Full payment processing with webhooks
- 🔄 **Review Management**: Dynamic review system with moderation
- 🔄 **Admin Dashboard**: User/course/review management interface
- 🔄 **Activity Tracking**: Comprehensive user behavior logging
- 🔄 **Wishlist System**: Course bookmarking functionality

### Future Enhancements
- 🔄 Advanced reporting and analytics
- 🔄 Certificate generation system
- 🔄 Mobile application development
- 🔄 REST API endpoints
- 🔄 Video streaming optimization
- 🔄 Multi-language support

---

**Built with ❤️ for the TMM Academy team**

Develop By team "Binary Coder"
