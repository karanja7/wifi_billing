# Render Deployment Guide

## ✅ Setup Complete!

The following files have been created for Render deployment:

- ✅ `Procfile` - Tells Render how to run your app
- ✅ `composer.json` - PHP dependencies
- ✅ `runtime.txt` - PHP version (8.2)
- ✅ `db_config.php` - Updated for environment variables
- ✅ `.gitignore` - Git ignore rules

---

## 📋 Next Steps

### Step 1: Initialize Git Repository

```powershell
cd C:\Users\mburu\Downloads\admin

# Initialize git if not already done
git init

# Add all files
git add .

# Commit
git commit -m "Prepare for Render deployment"
```

### Step 2: Create GitHub Repository

1. Go to https://github.com/new
2. Name: `wifi-billing`
3. Description: `WiFi Billing System with M-PESA`
4. Click **"Create repository"**

### Step 3: Push to GitHub

```powershell
# Add remote (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/wifi-billing.git

# Push to main branch
git branch -M main
git push -u origin main
```

### Step 4: Deploy on Render

1. Go to https://dashboard.render.com
2. Click **"New +"** → **"Web Service"**
3. Click **"Connect account"** → **"GitHub"**
4. Select your `wifi-billing` repository
5. Fill in settings:
   - **Name:** `wifi-billing`
   - **Environment:** `PHP`
   - **Build Command:** `composer install`
   - **Start Command:** Leave blank
   - **Plan:** Free tier

6. Click **"Create Web Service"**

### Step 5: Add Environment Variables

In Render dashboard, go to **Settings** → **Environment**

Add these variables:

**Database Connection:**
```
DB_HOST = (ask user where their MySQL database is hosted)
DB_USER = root
DB_PASS = (ask for password)
DB_NAME = wifi_billing
```

**M-PESA Configuration:**
```
MPESA_KEY = (ask for consumer key)
MPESA_SECRET = (ask for consumer secret)
MPESA_SHORTCODE = 174379
MPESA_PASSKEY = (ask for passkey)
```

### Step 6: Import Database Schema

After deployment, you need to import the database schema:

```powershell
# Option 1: Using MySQL command line
mysql -h DB_HOST -u root -p DB_NAME < schema_migration_v2.sql

# Option 2: Using phpMyAdmin if your database is web-accessible
# Upload schema_migration_v2.sql and import it
```

### Step 7: Test Your App

Once deployed, access your app at:
```
https://YOUR_APP_NAME.onrender.com
```

**Portal:** `https://YOUR_APP_NAME.onrender.com/portal.php`
**Admin:** `https://YOUR_APP_NAME.onrender.com/admin/index.php`

---

## 🔐 Information Needed from You

Before deployment, please provide:

1. **GitHub Username** - For creating the repository link
2. **Database Host** - Where your MySQL database is hosted
   - Example: `db.example.com` or IP address
   - Or do you want to use Render's PostgreSQL instead?
3. **Database Credentials:**
   - Username (usually: `root`)
   - Password
4. **M-PESA Credentials:**
   - Consumer Key
   - Consumer Secret
   - Business Shortcode (usually: `174379`)
   - Passkey
5. **Desired App Name** - What you want your Render app called
   - Example: `wifi-billing` or `mybilling`

---

## ⚠️ Important Notes

- **Database:** Make sure your MySQL database is accessible from Render (not just localhost)
- **M-PESA Callback URL:** Will be `https://YOUR_APP_NAME.onrender.com/api/mpesa/callback.php`
- **Free Tier:** Render free tier may have limitations. Upgrade if needed for production.
- **Cold Starts:** Free tier apps may take 30-60 seconds to start if idle.

---

## 🆘 Troubleshooting

**502 Bad Gateway:**
- Check build logs in Render
- Ensure database is accessible
- Check environment variables

**Database connection error:**
- Verify DB_HOST, DB_USER, DB_PASS
- Make sure MySQL allows remote connections
- Check firewall rules

**App won't deploy:**
- Check GitHub repository is public or Render has access
- Verify Procfile and composer.json are correct
- Check build command output

---

## ✨ Summary

You now have a production-ready WiFi billing system. Just provide the information above and follow the steps, and your app will be live on Render!
