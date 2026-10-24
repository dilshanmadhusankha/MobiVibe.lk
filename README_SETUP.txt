MobiVibe.lk — completion notes (ICT2142 Practical 06 & 07 / Week 07–08)

IMPORTANT
- Existing HTML page layouts, style.css and the original storefront UI have been retained. New checkout/admin pages were added separately.
- Run with XAMPP Apache + MySQL. Do not open HTML/PHP files with file://.
- Place the project folder inside C:\xampp\htdocs\ and browse to the folder URL.
- The database connection defaults in db_connection.php are host=localhost, user=root, password=1234, database=mobivibe_db. Change these to match your local MySQL setup.
- Open http://localhost/<your-project-folder>/database_setup.php once. It creates the existing schema and seeds the six phones shown in the original storefront. It is safe to re-run for those seed records. Delete/rename database_setup.php after setup on a public server.
- Run database_setup.php once. It creates a demo administrator automatically: email admin@mobivibe.lk, password Admin@123. Open account.html, switch to Login, sign in with these credentials, then open admin.php. Change this demo password before any public deployment. Admin access is checked server-side.
- Register ordinary customer accounts through account.html; these accounts do not receive admin permissions.
- Cart checkout redirects to checkout.php. The server looks up product prices and stock in the database; browser prices are not trusted. Orders/items and a pending payment record are saved before the payment hand-off.
- PayHere sandbox setup: create a sandbox account, add localhost as a Domain, then enter the Merchant ID and Merchant Secret in payhere_config.php. Check the return/cancel/notify URLs against the actual project URL. Do not commit a real merchant secret to GitHub.
- The notify endpoint verifies PayHere's MD5 signature, currency and amount before marking an order paid and reducing stock. A successful browser redirect alone does not mark payment as complete.
- The PayHere return/notify flow needs a publicly reachable callback URL for provider callbacks. For local testing, use an approved tunnel or deployed test host if PayHere cannot reach localhost.
- Admin dashboard supports product creation, editing, safe deactivation, stock visibility, order listing and order-status updates. SKU values must be unique.
- Home and Shop product cards now refresh from api_products.php when opened through localhost; newly added active products should appear in those catalog grids after refresh.
- This is a campus/demo project. Before production, add CSRF protection to state-changing forms, rate limiting, stronger audit logging, email verification, privacy/terms pages, and a reviewed payment notification implementation.
