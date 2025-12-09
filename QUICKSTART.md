# WiFi Billing System - Quick Start Guide

## 🚀 Fast Track Setup (5 Minutes)

### Step 1: Import Database
```powershell
cd C:\Users\mburu\Downloads\admin
cmd /c "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe -u root -pLikimani12$$ wifi_billing < schema_migration_v2.sql"
```

### Step 2: Start Server
```powershell
php -S localhost:8000
```

### Step 3: Test Portal
Visit: **http://localhost:8000/portal.php**

You should see:
- ✅ 5 WiFi plans with prices
- ✅ Phone number input field
- ✅ "Pay via M-PESA" button

### Step 4: Test Admin Dashboard
Visit: **http://localhost:8000/admin/index.php**

Login with:
- Username: **admin**
- Password: **admin123**

---

## 📋 Files Structure

```
admin/
├── portal.php                 ← Captive portal landing page
├── api/
│   ├── mpesa/
│   │   ├── stk_push.php      ← M-PESA payment initiation
│   │   └── callback.php      ← Payment callback handler
│   └── router.php            ← Router integration API
├── admin/
│   └── index.php             ← Admin dashboard
├── db_config.php             ← Database config (✅ password updated)
├── schema_migration_v2.sql   ← Database schema (✅ new)
├── DEPLOYMENT_GUIDE_v2.md    ← Complete setup guide
└── SYSTEM_SUMMARY.md         ← This summary

```

---

## 🔑 Key Credentials

**Admin Panel:**
- URL: http://localhost:8000/admin/index.php
- Username: admin
- Password: admin123

**Database:**
- Host: 127.0.0.1
- User: root
- Password: Likimani12$$
- Database: wifi_billing

**Default Plans:**
| Plan | Duration | Price |
|------|----------|-------|
| 1    | 1 Hour   | Ksh 10 |
| 2    | 3 Hours  | Ksh 20 |
| 3    | 6 Hours  | Ksh 40 |
| 4    | 12 Hours | Ksh 70 |
| 5    | 24 Hours | Ksh 120 |

---

## ⚙️ Configuration Checklist

- [ ] Database imported (schema_migration_v2.sql)
- [ ] PHP server started (php -S localhost:8000)
- [ ] Portal accessible (http://localhost:8000/portal.php)
- [ ] Admin dashboard accessible (http://localhost:8000/admin/index.php)
- [ ] M-PESA credentials obtained from Safaricom
- [ ] M-PESA config updated in database
- [ ] Router credentials updated in database
- [ ] Domain name configured
- [ ] SSL certificate installed
- [ ] Live!

---

## 🧪 Quick Test

1. Go to http://localhost:8000/portal.php
2. Select "1 Hour - Ksh 10" plan
3. Enter phone: 254712345678
4. Click "Pay via M-PESA"
5. Check database:
   ```sql
   SELECT * FROM payments ORDER BY created_at DESC LIMIT 1;
   SELECT * FROM users ORDER BY created_at DESC LIMIT 1;
   ```

---

## 📖 Full Guides

- **DEPLOYMENT_GUIDE_v2.md** - Complete setup with all details
- **SYSTEM_SUMMARY.md** - System architecture and features

---

## 🎯 What Each File Does

| File | Endpoint | Purpose |
|------|----------|---------|
| portal.php | /portal.php | User landing page (select plan, enter phone) |
| stk_push.php | /api/mpesa/stk_push.php | Send M-PESA STK push to phone |
| callback.php | /api/mpesa/callback.php | Receive payment confirmation from Safaricom |
| router.php | /api/router.php | Authorize/revoke device MAC on router |
| admin/index.php | /admin/index.php | Admin dashboard with stats |

---

## 🔄 Payment Flow (Simplified)

1. User → Portal (select plan + phone)
2. Backend → Safaricom (send STK push)
3. User → Phone (confirm M-PESA PIN)
4. Safaricom → Backend (payment callback)
5. Backend → Router (authorize MAC address)
6. User → WiFi (full internet access)

---

## 🛠️ Common Commands

**Check MySQL:**
```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ -e "SELECT 1"
```

**View Payments:**
```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ wifi_billing -e "SELECT * FROM payments;"
```

**View Sessions:**
```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ wifi_billing -e "SELECT * FROM sessions;"
```

**View Users:**
```powershell
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -pLikimani12$$ wifi_billing -e "SELECT * FROM users;"
```

---

## ❌ Troubleshooting

**"Cannot connect to database"**
- Check MySQL is running
- Verify password is correct in db_config.php
- Run: `mysql -u root -p` to test connection

**"Portal page won't load"**
- Check PHP server is running: `php -S localhost:8000`
- Check PHP is installed: `php --version`
- Check port 8000 is not in use

**"M-PESA payment not working"**
- Check credentials in mpesa_config table
- Check callback URL is correct
- Check logs in `/logs/mpesa_callbacks.log`
- Verify test_mode=1 for sandbox testing

**"Router authorization failing"**
- Check router credentials in router_config table
- Check router is accessible at router_ip
- Check network connectivity
- Verify router API credentials are correct

---

## 📞 Support

1. Check **DEPLOYMENT_GUIDE_v2.md** for detailed help
2. Check logs in `/logs/` directory
3. Query `access_logs` table in database
4. Review `mpesa_callbacks.log` for payment issues

---

**Ready to go live?** Read DEPLOYMENT_GUIDE_v2.md for production setup!
