# Auto Gold Price Widget - File Structure Documentation

## 📁 Plugin Structure

```
gold-price-cal/
├── auto-gold-price-widget.php    # Main plugin file (entry point)
├── README.md                      # Documentation
│
├── includes/                      # Core functionality
│   └── class-agpw-core.php       # API fetching, calculations, data storage
│
├── admin/                         # Admin panel
│   └── class-agpw-admin.php      # Settings page, field callbacks
│
├── public/                        # Frontend display
│   └── class-agpw-public.php     # Shortcode handler, rendering
│
└── assets/                        # Static files
    ├── css/
    │   └── agpw-style.css        # All styles
    └── js/
        └── agpw-script.js        # Frontend JavaScript
```

## 🔧 File Descriptions

### **auto-gold-price-widget.php**
- Main plugin file that WordPress loads
- Defines constants and includes all class files
- Registers hooks, actions, and shortcodes
- Maintains backward compatibility

### **includes/class-agpw-core.php**
- `AGPW_Core` class
- Handles API communication (GoldAPI.io, ExchangeRate-API)
- Calculates gold prices for all karats
- Manages exchange rate conversions
- Stores and retrieves data from WordPress options

### **admin/class-agpw-admin.php**
- `AGPW_Admin` class
- Creates WordPress admin settings page
- **NEW: API Data Display Section** - Shows current gold price, exchange rate, and currency selector
- Registers all settings fields
- Handles form callbacks and validation

### **public/class-agpw-public.php**
- `AGPW_Public` class
- Handles `[gold_price]` shortcode
- Renders different display types (table, grid, ticker, single)
- Enqueues CSS and JavaScript
- Passes configuration to frontend

### **assets/css/agpw-style.css**
- All plugin styles
- Theme variations (default, dark, glass, luxury)
- Responsive design

### **assets/js/agpw-script.js**
- Price volatility animation
- Real-time clock display
- Frontend interactions

## ✨ New Features (v2.1)

### 1. **Better File Organization**
- Separated concerns into logical files
- Easier to maintain and extend
- Clear class-based structure

### 2. **API Data Display Section**
Located at the **top** of the admin settings page, this section shows:

- **Gold Spot Price (USD/oz)**: Current gold price from API
- **USD to Currency Rate**: Exchange rate for selected currency
- **Last Updated**: Timestamp of last API fetch
- **Currency Selector**: Dropdown to change the entire plugin's currency
  - Automatically updates the default currency setting
  - Shows all available currencies from ExchangeRate-API
  - Affects all widgets and calculations

### 3. **Global Currency Management**
- Change currency from one location
- All shortcodes respect the default currency
- Individual shortcodes can still override with `currency` attribute
- Example: `[gold_price currency="USD"]`

## 🎯 Usage

### Admin Settings
1. Go to **Settings > Gold Price Settings**
2. View API data in the top section
3. Use the currency selector to change the plugin's currency
4. Configure other settings as needed
5. Click "Save Settings"

### Shortcode Examples

```php
// Basic table with default currency
[gold_price type="table"]

// Grid view with specific currency
[gold_price type="grid" currency="USD"]

// Single karat display
[gold_price type="single" karat="24"]

// With custom title and date
[gold_price type="table" title="Today's Gold Prices" date="yes"]

// With custom theme and karats
[gold_price type="grid" karat="24,22,21,18" theme="dark"]
```

## 🔄 Migration Notes

- All existing functionality is preserved
- Old shortcodes continue to work
- Settings are maintained
- No data loss during update

## 🛠️ Developer Notes

### Adding New Features
- **Core logic**: Add to `AGPW_Core` class
- **Admin settings**: Add to `AGPW_Admin` class
- **Frontend display**: Add to `AGPW_Public` class

### Hooks Available
- `agpw_update_gold_prices` - Scheduled price update
- Standard WordPress hooks for settings and shortcodes

## 📝 Version History

- **v2.1** - Restructured file organization, added API Data Display section
- **v2.0** - Added volatility animation, timezone support, multiple currencies
- **v1.0** - Initial release
