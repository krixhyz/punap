# Punap - Sustainable Solutions Platform

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## 🎨 Design System

This project features a cohesive, minimalistic design with a green and white color palette focused on sustainability and clarity.

### Color Palette
- **Primary Green**: `#22c55e` - Main brand color
- **Accent Green**: `#10b981` - Secondary actions and highlights
- **White/Gray**: Clean backgrounds and text hierarchy
- **Semantic Colors**: Success, warning, and error states

### Design Principles
- **Minimalistic**: Clean, uncluttered interfaces
- **Accessible**: High contrast ratios and clear typography
- **Responsive**: Mobile-first, fluid layouts
- **Smooth**: Subtle animations and transitions
- **Consistent**: Unified component library

### Component Library
- Buttons (primary, secondary, ghost)
- Cards (elevated, flat)
- Forms (inputs, checkboxes, labels)
- Navigation (links, dropdowns)
- Badges and alerts
- Hero sections and layouts

### Typography
- **Font**: Inter (Google Fonts)
- **Hierarchy**: Clear heading scales (h1-h6)
- **Readability**: Optimized line heights and spacing

## 🚀 Getting Started

```bash
# Install dependencies
npm install
composer install

# Create local environment
cp .env.example .env

# Run development server
npm run dev
php artisan serve
```

## 📦 Tech Stack

- **Framework**: Laravel + Vite
- **Styling**: Tailwind CSS (custom configuration)
- **JavaScript**: Alpine.js for interactivity
- **Realtime**: Laravel Echo + Pusher
- **Payments**: eSewa

## 🔐 Production Configuration

- Keep real secrets out of source control. Only commit [.env.example](d:/Projects%20Y3/FYP/.env.example) with placeholder values.
- Provide `APP_KEY`, database credentials, Pusher keys, and eSewa credentials through your deployment environment or secret manager.
- Use Redis for `QUEUE_CONNECTION` and `CACHE_STORE` in production.
- Keep [.env](d:/Projects%20Y3/FYP/.env) local only. It is already ignored by [.gitignore](d:/Projects%20Y3/FYP/.gitignore).

## 🎯 Usage

Use the pre-built component classes in your Blade templates:

```html
<!-- Buttons -->
<button class="btn-primary">Click Me</button>
<button class="btn-secondary">Secondary</button>

<!-- Cards -->
<div class="card">Your content</div>

<!-- Forms -->
<input type="text" class="input" placeholder="Enter text">

<!-- Badges -->
<span class="badge-primary">New</span>
```

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
