# Digital Queue Management System

A comprehensive queue management system built with Laravel that supports both walk-in and online booking with real-time queue display and admin management.

## Features

### User Features
- **Walk-in Tickets**: Generate tickets via QR code scanning
- **Online Booking**: Schedule appointments with time slots
- **Real-time Queue Display**: Live view of current queue status
- **Ticket Status Tracking**: Check individual ticket status
- **Multi-language Support**: Interface in Indonesian and English

### Admin Features
- **Admin Dashboard**: Complete queue management interface
- **Queue Control**: Call next, skip, complete, or cancel tickets
- **QR Token Management**: Generate and regenerate QR tokens
- **Settings Management**: Configure operating hours and slot settings
- **Statistics**: View daily queue statistics and history

## Technical Specifications

### Tech Stack
- **Backend**: Laravel 11
- **Frontend**: Blade templates with Bootstrap
- **Database**: MySQL
- **Queue System**: Database-driven queue management
- **Authentication**: Session-based admin authentication

### Key Components

#### Models
- **Queue**: Main queue management model
- **QrToken**: QR code token management
- **Setting**: System configuration settings

#### Controllers
- **QueueController**: Handles all queue operations
- **Admin Authentication**: Secure admin access with PIN

#### Views
- **User Interface**: Index, booking, scan, ticket, display pages
- **Admin Interface**: Login, dashboard, QR display pages

## Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- Node.js (for frontend assets)

### Setup Instructions

1. **Clone the repository**
```bash
git clone https://github.com/Moonzayn/Digital-Queue.git
cd Digital-Queue
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate
```

5. **Generate QR tokens**
```bash
php artisan queue:work
```

6. **Build frontend assets**
```bash
npm run build
```

7. **Start the application**
```bash
php artisan serve
```

## Configuration

### Admin Settings
- **Admin PIN**: Set in `.env` file (default: 1234)
- **Operating Hours**: Configure in admin dashboard
- **Slot Duration**: Set booking slot duration (10, 15, 20, or 30 minutes)
- **Slot Capacity**: Set maximum bookings per slot

### QR Token Settings
- **Token Expiration**: Configurable expiration time
- **Rate Limiting**: IP-based rate limiting for walk-in tickets

## Usage

### User Workflow

#### Walk-in Users
1. **Scan QR Code**: Scan the displayed QR code to get a walk-in ticket
2. **Get Ticket**: Receive a ticket with unique code and estimated wait time
3. **Wait**: Wait for your ticket number to be called
4. **Check Status**: View current queue position and estimated wait time
5. **Present Ticket**: Show ticket when your number is called

#### Online Booking Users
1. **Select Time Slot**: Choose available time slot from the booking interface
2. **Confirm Booking**: Provide customer name and confirm booking
3. **Receive Reserved Ticket**: Get a ticket with "reserved" status and scheduled time
4. **Scan QR Code**: Scan your QR code at least 10 minutes before scheduled time to secure priority
   - **From Booking Interface**: Click "Mulai Scan" button in the booking page
   - **From Ticket Page**: Use the QR code displayed on your ticket
5. **Priority Status**:
   - **On-time Scan**: Ticket maintains priority and is treated as "online on time"
   - **Late Scan**: Ticket loses priority and is treated as walk-in (FIFO order)
   - **No Scan**: Ticket cannot enter queue and may be auto-cancelled
6. **Wait**: Wait for your ticket number to be called based on priority and timestamp
7. **Check Status**: View current queue position and estimated wait time
8. **Present Ticket**: Show ticket when your number is called
5. **Priority Status**:
   - **On-time Scan**: Ticket maintains priority and is treated as "online on time"
   - **Late Scan**: Ticket loses priority and is treated as walk-in (FIFO order)
   - **No Scan**: Ticket cannot enter queue and may be auto-cancelled
6. **Wait**: Wait for your ticket number to be called based on priority and timestamp
7. **Check Status**: View current queue position and estimated wait time
8. **Present Ticket**: Show ticket when your number is called

### Queue Priority & Ordering
- **Priority Levels**:
  1. Online users who scanned on time
  2. Walk-in users
  3. Online users who scanned late
- **Ordering**: Within each priority level, tickets are ordered by timestamp (scan/arrival time)
- **Admin Actions**: CALL NEXT, SKIP, CANCEL, COMPLETE work identically for all tickets

### Edge Cases
- **Online ticket not scanned**: Cannot enter queue, auto-cancelled after scheduled time
- **Multiple scans at exact same time**: Tie-break by ticket ID ascending
- **Late online bookings**: Treated as walk-in users with no priority

### Admin Workflow
1. **Login**: Access admin dashboard with PIN
2. **Manage Queue**: Call next ticket, skip, complete, or cancel
3. **Settings**: Configure system settings and operating hours
4. **Statistics**: Monitor daily queue performance

## API Endpoints

### Public APIs
- `GET /api/live-status`: Get current queue status
- `GET /api/public-queue`: Get public queue list
- `GET /api/ticket-status/{code}`: Check ticket status

### Admin APIs
- `POST /api/admin/call-next`: Call next ticket
- `POST /api/admin/skip-current`: Skip current ticket
- `POST /api/admin/complete-current`: Complete current ticket
- `POST /api/admin/cancel-ticket/{id}`: Cancel specific ticket
- `POST /api/admin/reset-queue`: Reset all active tickets

## Database Schema

### Queues Table
- `ticket_number`: Unique ticket identifier
- `customer_name`: Customer name (optional)
- `unique_code`: Unique ticket code for status checking
- `type`: 'walk-in' or 'online'
- `status`: 'waiting', 'reserved', 'serving', 'completed', 'skipped', 'cancelled'
- `scheduled_at`: Appointment time for online bookings
- `called_at`: Time when ticket was called
- `completed_at`: Time when ticket was completed
- `ip_address`: IP address for rate limiting

### QR Tokens Table
- `token`: Unique QR token
- `expires_at`: Token expiration time

### Settings Table
- `key`: Setting identifier
- `value`: Setting value
- `description`: Setting description

## Security Features

- **Admin Authentication**: PIN-based session authentication
- **Rate Limiting**: IP-based rate limiting for ticket generation
- **Input Validation**: Comprehensive request validation
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Blade template auto-escaping

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the MIT license.

## Support

For support and questions, please open an issue in the repository.