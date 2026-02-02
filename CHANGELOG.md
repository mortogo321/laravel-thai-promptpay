# Changelog

All notable changes to `laravel-thai-promptpay` will be documented in this file.

## v2.0.5 - 2026-02-02

### Changed
- Updated example page to show all supported identifier types (Mobile, National ID, Tax ID, e-Wallet)
- API `/promptpay/generate` now returns `type` field indicating identifier type
- Added PHPUnit 12.x support in composer.json

### Improved
- Example page now displays identifier type in results
- Added format hints below identifier input field

## v2.0.4 - 2026-02-02

### Fixed
- **QR Code Compliance:** Corrected PromptPay payload format to strictly follow BOT (Bank of Thailand) Thai QR Payment specification
- **Sub-tag Handling:** Fixed sub-tag usage per BOT spec - Mobile (01), National ID/Tax ID (02), e-Wallet (03)
- **Phone Number Format:** Accept 10-digit phone numbers without leading 0 (e.g., `812345678` → `0066812345678`)

### Added
- `validate(string $identifier)` - Validate identifier without generating QR code
- `validateAmount(?float $amount)` - Validate payment amount with proper range checking
- `getIdentifierType(string $identifier)` - Get identifier type ('mobile', 'tax_id', 'ewallet')
- `isMobileNumber(string $identifier)` - Check if valid Thai mobile (validates 06/08/09 prefix)
- `isNationalId(string $identifier)` - Check if valid National ID with checksum validation
- `isTaxId(string $identifier)` - Check if valid Tax ID
- `getSupportedFormats()` - Get list of all supported identifier formats with sub-tags

### Changed
- Improved README documentation with badges, validation examples, and supported formats table
- Enhanced error messages for invalid identifier formats
- Clarified that bank account numbers are NOT supported (BOT spec marks them as "reserved")

## v1.0.1 - 2025-10-10

### Changed
- Updated README badges to use GitHub license badge instead of Packagist
- Improved badge consistency and styling

## v1.0.0 - Initial Release

### Added
- Initial release
- Generate PromptPay QR codes for Thai phone numbers
- Generate PromptPay QR codes for Thai National ID
- Support for fixed amount payments
- Support for open amount (user enters amount)
- Returns QR code as data URI or binary PNG
- Built-in AJAX/API endpoints for dynamic generation
- Works with Axios, Fetch, Vue.js, React, and any frontend framework
- Beautiful example page included
- Laravel auto-discovery support
- Fully compliant with Thai PromptPay EMV QR Code specification
- Support for Laravel 10.x and 11.x
- Support for PHP 8.1+
