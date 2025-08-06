=== Villa Reservation System ===
Contributors: villadev
Tags: reservation, booking, villa, woocommerce, google-sheets
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional villa reservation system with WooCommerce integration and Google Sheets synchronization.

== Description ==

Villa Reservation System is a comprehensive booking solution designed specifically for villa and vacation rental businesses. It seamlessly integrates with WooCommerce and provides real-time synchronization with Google Sheets for availability management.

**Key Features:**

* **WooCommerce Integration**
  * Separate WooCommerce product for each villa
  * Date picker on product pages
  * Automatic order creation on reservation
  * Daily/weekly/monthly pricing support
  * Inventory management (available/booked dates)

* **Advanced Reservation System**
  * User-friendly date selection interface
  * Conflict prevention for overlapping reservations
  * Check-in / Check-out date control
  * Minimum/maximum stay settings
  * Automatic price calculation
  * Guest capacity management (adults/children with age groups)

* **Google Sheets Integration**
  * Separate Google Sheets table per villa
  * Two-way synchronization
  * Colored cells in Sheets = blocked dates on site
  * Site reservations = automatic coloring in Sheets
  * Real-time updates using Google Sheets API v4

* **Professional Admin Panel**
  * Villa management interface
  * Google Sheets URL linking system
  * Reservation calendar view
  * Manual date blocking/unblocking
  * Reservation history and reports
  * Price management system

* **Enhanced Customer Experience**
  * Responsive date picker
  * Real-time availability checking
  * Detailed reservation form with guest options
  * Reservation summary and confirmation
  * User account reservation history
  * Mobile-friendly interface

* **Complete Email System**
  * Reservation confirmation emails (customer)
  * New reservation notifications (admin)
  * Reminder emails before check-in
  * Cancellation notifications
  * Fully customizable Turkish email templates

**Technical Features:**

* PHP 7.4+ compatibility
* WordPress hooks and filters integration
* Custom post types for villas
* AJAX-powered real-time interactions
* Automated cron jobs for synchronization
* Comprehensive security measures
* Responsive CSS3 design
* Clean uninstall with data removal

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/villa-reservation-system` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and activated.
4. Go to Villa Reservation > Google Sheets Settings to configure Google Sheets integration (optional).
5. Create your first villa from the Villas menu.
6. Configure villa settings, pricing, and Google Sheets connection.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes, WooCommerce is required for payment processing and order management.

= How do I set up Google Sheets integration? =

1. Create a Google Cloud Console project
2. Enable Google Sheets API
3. Create a service account and download JSON credentials
4. Upload the JSON file in Villa Reservation > Google Sheets Settings
5. Share your Google Sheet with the service account email
6. Enter the Sheet URL in villa settings

= Can I customize the email templates? =

Yes, email templates are located in the plugin's templates/emails/ directory and can be customized.

= Is the plugin translation ready? =

Yes, the plugin includes full Turkish translation and is ready for additional translations.

= What pricing models are supported? =

The plugin supports both fixed pricing per night and per-person pricing with separate rates for adults and children.

= Can I block dates manually? =

Yes, you can manually block dates from the admin calendar view or through Google Sheets.

== Screenshots ==

1. Villa reservation form with date picker and guest selection
2. Admin villa management interface
3. Google Sheets integration settings
4. Reservation calendar view
5. Customer reservation history
6. Email template example

== Changelog ==

= 1.0.0 =
* Initial release
* WooCommerce integration
* Google Sheets synchronization
* Villa management system
* Email notifications
* Customer portal
* Admin dashboard
* Turkish language support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Villa Reservation System.

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* WooCommerce 5.0 or higher
* MySQL 5.6 or higher

== Support ==

For support and documentation, please visit the plugin documentation or contact the development team.

== Development ==

This plugin follows WordPress coding standards and includes:

* Proper sanitization and validation
* Nonce verification for security
* AJAX with proper authentication
* Database queries using $wpdb
* Custom post types and taxonomies
* Hook-based architecture
* Responsive design principles

== Google Sheets Setup Guide ==

1. Go to Google Cloud Console (console.cloud.google.com)
2. Create a new project or select existing one
3. Enable Google Sheets API
4. Go to Credentials > Create Credentials > Service Account
5. Download the JSON key file
6. Upload this file in the plugin settings
7. Share your Google Sheet with the service account email (found in JSON file)
8. Use the following format in your sheet:
   * Column A: Dates (YYYY-MM-DD format)
   * Column B: Status (color cells to block dates)

== Customization ==

The plugin includes several hooks for customization:

* `vrs_reservation_created` - Fired when a new reservation is created
* `vrs_reservation_confirmed` - Fired when a reservation is confirmed
* `vrs_reservation_cancelled` - Fired when a reservation is cancelled
* `vrs_google_sheets_synced` - Fired after Google Sheets sync

Template files can be overridden by copying them to your theme's `villa-reservation-system/` directory.