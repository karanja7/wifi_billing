# WiFi Billing System - Captive Portal with M-PESA
## Complete Deployment & Setup Guide v2.0

---

## **📋 System Overview**

Your WiFi billing system is now a **captive portal payment system** that works as follows:

1. **User connects to WiFi** → Redirected to portal page
2. **User selects a plan** and enters phone number
3. **M-PESA STK push** is sent to their phone
4. **User confirms payment** with M-PESA PIN
5. **Payment callback** verifies success
6. **Router is told to authorize** the device's MAC address
7. **User gets full internet access** until subscription expires

---

## **🗄️ NEW Database Schema (v2.0)**

Run the new migration file to update your database:

```powershell
# Using cmd to avoid PowerShell redirection issues
cmd /c "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe -u root -pLikimani12$$ wifi_billing < C:\Users\mburu\Downloads\admin\schema_migration_v2.sql"
```

### **Key Tables:**
- **users** - Phone-based user accounts (no password needed)
- **devices** - MAC address tracking
- **payments** - M-PESA transactions
- **sessions** - Active WiFi sessions (MAC + duration)
- **router_config** - Router API credentials
- **mpesa_config** - M-PESA credentials
- **access_logs** - Complete audit trail

### **Plans Created:**
| Duration | Price |
|----------|-------|
| 1 Hour   | Ksh 10 |
| 3 Hours  | Ksh 20 |
| 6 Hours  | Ksh 40 |
| 12 Hours | Ksh 70 |
| 24 Hours | Ksh 120 |

---

## **📍 System Flow (Step by Step)**

### **Step 1: User Connects to WiFi**
```
Device connects to SSID
↓
Router runs captive portal (CoovaChilli, nodogsplash, etc.)
↓
Device tries to access any website
↓
Router intercepts and redirects to:
http://yourdomain.com/portal.php?mac=[MAC_ADDRESS]
```

### **Step 2: Portal Page**
The user sees `/portal.php`:
- List of available plans with prices (1hr-Ksh10, 3hr-Ksh20, etc.)
- Input field for phone number
- "Pay via M-PESA" button

### **Step 3: M-PESA STK Push**
When user clicks "Pay":
1. Form submits to `/api/mpesa/stk_push.php`
2. Backend creates user record (if new)
3. Payment record created with status='pending'
4. M-PESA API is called:
   ```
   POST https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest
   ```
5. User receives STK prompt on phone

### **Step 4: User Confirms Payment**
User enters M-PESA PIN on their phone.

### **Step 5: M-PESA Callback**
Safaricom sends callback to `/api/mpesa/callback.php`:
- If successful: Creates WiFi session with expiry time
- Logs to `sessions` table
- Updates router with device MAC address

### **Step 6: Router Authorization**
Backend calls `/api/router.php?action=authorize`:
- Gets router credentials from DB
- Sends device MAC to router
- Router unblocks the MAC address

### **Step 7: Full Internet Access**
Device now has unrestricted internet until session expires.

### **Step 8: Session Expiry**
- Cron job checks expired sessions
- Calls router to revoke MAC address
- Device redirected back to portal

---

## **🛠️ Setup Instructions**

### **Step 1: Update Database Password**

```powershell
# Open MySQL CLI
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p wifi_billing

# When prompted, enter: Likimani12$$

# Update password hash (already done, but verify):
UPDATE admins SET password = '$2y$10$O2YBZLVj1k3PwqZ.5qZf..XvPH2j8rNBqfNn0T5yPWzGHlqmz2mZi' WHERE id = 1;

# Exit MySQL
EXIT;
```

### **Step 2: Import New Schema**

```powershell
cd C:\Users\mburu\Downloads\admin

cmd /c "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe -u root -pLikimani12$$ wifi_billing < schema_migration_v2.sql"
```

### **Step 3: Configure M-PESA**

Get credentials from Safaricom Daraja API:
https://developer.safaricom.co.ke/

Then update the database:

```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ wifi_billing

UPDATE mpesa_config SET
  consumer_key = 'YOUR_CONSUMER_KEY',
  consumer_secret = 'YOUR_CONSUMER_SECRET',
  business_shortcode = '174379',
  passkey = 'YOUR_PASSKEY',
  callback_url = 'https://yourdomain.com/api/mpesa/callback.php',
  test_mode = 1
WHERE id = 1;

EXIT;
```

### **Step 4: Configure Router**

Update router credentials in database:

```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ wifi_billing

-- For MikroTik:
UPDATE router_config SET
  router_type = 'mikrotik',
  router_ip = '192.168.1.1',
  router_username = 'admin',
  router_password = 'your_password',
  router_port = 8728,
  is_active = 1
WHERE id = 1;

-- For CoovaChilli:
UPDATE router_config SET
  router_type = 'coovachilli',
  router_ip = '192.168.1.1',
  router_username = 'admin',
  router_password = 'password',
  is_active = 1
WHERE id = 1;

EXIT;
```

### **Step 5: Start PHP Server (Testing)**

```powershell
cd C:\Users\mburu\Downloads\admin
php -S localhost:8000
```

Then visit: **http://localhost:8000/portal.php**

### **Step 6: Test Payment Flow**

1. Select a plan (e.g., 1 Hour - Ksh 10)
2. Enter test phone number: **254712345678**
3. Click "Pay via M-PESA"
4. Check database for:
   - New user created in `users` table
   - Payment record in `payments` table
   - Session record in `sessions` table (after callback)

---

## **📁 Key Files**

| File | Purpose |
|------|---------|
| `portal.php` | Main captive portal landing page |
| `api/mpesa/stk_push.php` | Initiates M-PESA payment |
| `api/mpesa/callback.php` | Handles M-PESA callback |
| `api/router.php` | Manages router device authorization |
| `admin/index.php` | Admin dashboard |
| `db_config.php` | Database configuration |
| `schema_migration_v2.sql` | Complete database schema |

---

## **🔧 Router Integration**

### **MikroTik Example:**

```bash
# Via MikroTik API
POST /ip/hotspot/user/add
{
    "name": "user_abc123",
    "profile": "default",
    "disabled": "false"
}

# Via SSH (simpler)
ssh admin@192.168.1.1
/ip hotspot user add name=user_abc123 password=pass
```

### **CoovaChilli Example:**

```bash
# SSH to router
chilli_query authorize [MAC_ADDRESS]

# Or:
chilli_query -json -a authorize -n [MAC_ADDRESS] -t [DURATION_MINUTES]
```

### **Nodogsplash Example:**

```bash
# SSH to router
ndsctl auth 1 [MAC_ADDRESS]
ndsctl deauth [MAC_ADDRESS]  # to revoke
```

### **pfSense Example:**

```bash
# Via SSH + captive portal config
ssh admin@pfsense.local
# pfSense stores authorized MACs in database
# Can authenticate via:
# - API endpoint
# - Direct database update
```

---

## **🚀 Production Deployment**

### **Option 1: XAMPP**
1. Copy entire admin folder to `C:\xampp\htdocs\wifibilling`
2. Access via: `http://localhost/wifibilling/portal.php`
3. Configure virtual host for domain

### **Option 2: Laragon**
1. Copy to `C:\laragon\www\wifibilling`
2. Create virtual host
3. Access via: `http://wifibilling.local/portal.php`

### **Option 3: Linux/Docker**
```bash
# Install PHP + MySQL
docker run -d -p 3306:3306 mysql:8.0
docker run -d -p 80:8080 php:7.4-apache

# Copy files to container
docker cp /path/to/admin /var/www/html/

# Run migration
docker exec mysql mysql -u root -p wifi_billing < schema_migration_v2.sql
```

---

## **📊 Admin Dashboard**

Access at: **http://yourdomain.com/admin/index.php**

Login credentials:
- Username: **admin**
- Password: **admin123** (change immediately!)

Features:
- Real-time dashboard with stats
- Payment history
- Active sessions
- User management
- Router configuration
- M-PESA settings

---

## **🔐 Security Checklist**

- [ ] Change admin password
- [ ] Update M-PESA credentials (test mode → production)
- [ ] Configure router credentials
- [ ] Enable HTTPS (certificate required)
- [ ] Set callback URL to HTTPS
- [ ] Set up firewalls
- [ ] Enable logging
- [ ] Set up automated backups
- [ ] Configure session timeouts

---

## **📞 Troubleshooting**

### **M-PESA Payment Not Working**
1. Check M-PESA credentials in database
2. Verify callback URL is accessible
3. Check logs in `/logs/mpesa_callbacks.log`
4. Ensure test mode matches environment

### **Router Authorization Failing**
1. Verify router IP and credentials
2. Check router connectivity
3. Check logs in `access_logs` table
4. Test router API manually

### **Payment Succeeds but No Session**
1. Check callback URL is correct
2. Verify callback endpoint accessible
3. Check database for payment record
4. Check error logs

### **Database Connection Error**
1. Update `db_config.php` with correct password
2. Verify MySQL is running
3. Check user permissions
4. Test connection: `mysql -u root -p`

---

## **📈 Monitoring & Maintenance**

### **Daily Checks:**
```sql
-- Check pending payments
SELECT * FROM payments WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Check expired sessions
SELECT * FROM sessions WHERE status = 'active' AND end_time < NOW();

-- Check error logs
SELECT * FROM access_logs WHERE status = 'error' ORDER BY created_at DESC LIMIT 20;
```

### **Weekly Tasks:**
- Review payment reconciliation
- Check for failed transactions
- Review user complaints
- Check system logs

### **Monthly Tasks:**
- Database optimization
- Backup verification
- Security audit
- Performance review

---

## **💡 Next Steps**

1. ✅ Import new database schema
2. ✅ Configure M-PESA credentials
3. ✅ Configure router credentials
4. ✅ Test payment flow
5. ⏳ Set up domain name
6. ⏳ Configure HTTPS/SSL
7. ⏳ Set up captive portal redirect on router
8. ⏳ Train support staff
9. ⏳ Go live!

---

## **📧 Support**

For issues or questions:
- Check logs in `/logs/` directory
- Review `access_logs` table in database
- Check M-PESA callback logs
- Verify database connectivity

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

**Change password immediately after first login!**

---

**System Created: December 8, 2024**
**Last Updated: December 8, 2024**
