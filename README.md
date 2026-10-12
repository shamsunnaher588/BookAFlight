✈️ BookAFlight

A Flight Search & Smart Travel Management Web Application

Explore Flights. Plan Journeys. Manage Travel — All in One Place.

Project Type: Full-Stack Web Application
Core Technologies: PHP, MySQL, HTML5, CSS3 & JavaScript
Development Environment: XAMPP & Visual Studio Code
Project Category: Flight Search, Demo Booking & Travel Management


🌐 Project Overview

BookAFlight is a dynamic, database-driven web application developed to provide an integrated flight search, journey planning, and travel management experience through a modern and user-friendly digital platform.

The application combines essential travel services, including flight discovery, multi-city journey planning, interactive seat selection, passenger management, booking history, flight status tracking, and personalized travel assistance.

Built using PHP and MySQL, BookAFlight demonstrates practical full-stack web development concepts through interactive frontend components, server-side processing, relational database management, authentication, and third-party API integration.

The primary objective of this project is to create an organized, accessible, and responsive travel management platform that simplifies the experience of exploring flights and managing demonstration travel itineraries.

Designed as an academic prototype, BookAFlight emphasizes functionality, usability, modular development, and the potential for future integration with real-world airline reservation and payment services.

🎯 Project Objectives

- Develop a centralized and user-friendly flight search platform.
- Support one-way, round-trip, and multi-city journey planning.
- Simplify passenger information and demo booking management.
- Provide interactive seat selection and itinerary visualization.
- Maintain organized user accounts, profiles, and journey records.
- Integrate external flight tracking and geographical mapping services.
- Demonstrate secure database-driven application development.
- Design a responsive interface for different screen sizes.
- Establish a foundation for advanced flight booking and travel management functionality.


🚀 Key Features

✈️ 1. Flight Search & Discovery

BookAFlight provides an organized interface for searching and exploring available flight options.

- One-Way Flight Search: Explore flights between selected destinations.
- Round-Trip Search: Plan outbound and return journeys.
- Multi-City Search: Create travel plans involving multiple destinations.
- Airport Auto-Suggestion: Simplify airport and destination selection.
- Origin–Destination Swap: Quickly exchange departure and arrival locations.
- Flight Filtering & Sorting: Refine available flight results.
- Flight Comparison: Compare flight options and illustrative fares.
- Flight Information: View airline, route, departure, arrival, and fare details.

🎫 2. Booking & Journey Management

The system provides a demonstration booking workflow with passenger details and locally stored journey information.

- Interactive Seat Selection: Choose seats using a visual seat map.
- Passenger Information: Enter and validate traveler details.
- Multi-Passenger Support: Prepare travel plans for multiple passengers.
- Demo Booking Workflow: Save selected flight and passenger information.
- Unique Local References: Generate internal identifiers for saved bookings.
- Linked Journey Plans: Organize multiple flight segments under one journey.
- My Bookings: Access previously saved flight plans.
- Journey History: Review and manage personal itinerary records.
- Demo Boarding Pass: Generate and print demonstration travel documents.
- Itinerary Sharing: Share supported journey information using generated links.

👤 3. User Account & Personalization

BookAFlight includes account management functionality for a personalized travel planning experience.

- User Registration
- Secure Login and Logout
- Session-Based Authentication
- Personal User Dashboard
- Profile Information Management
- Profile Picture Upload and Removal
- Saved Passenger Profiles
- Passenger Information Auto-Fill
- Personal Booking and Journey History

These features help users organize their travel-related information and reduce repetitive data entry.

🌍 4. Flight Tracking & Travel Assistance

The application incorporates external information services to provide additional travel-related functionality.

- Flight Status Lookup: Retrieve flight status information using the AirLabs API when configured.
- Flight Information Display: View available operational and schedule data.
- Interactive Maps: Visualize geographic information using Leaflet and OpenStreetMap.
- Airport & Route Visualization: Explore supported travel routes.
- Travel Guide: Access destination-related information and travel guidance.
- Hotel Preview: Explore accommodation-related information.
- Car Rental Preview: Access transportation-related options.
- Group Booking Request: Support group travel planning inquiries.

Flight tracking depends on external provider availability and valid API credentials.

💬 5. Customer Support & Assistance

BookAFlight includes support-oriented features designed to improve accessibility and user assistance.

- FAQ and Help Center
- Organized Travel-Related Questions and Answers
- Customer Support Interfaces
- User-to-Admin Communication Features
- Support Message History and Attachments in supported modules

🛡️ 6. Administration & Application Management

The project incorporates administrative and system-management functionality to support backend operations.

- Administrative Management Interfaces
- Flight and Booking Data Management Components
- User-Related Management Functionality
- Maintenance Mode
- Server-Side Input Validation
- Session-Based Access Control
- CSRF Protection in supported forms
- Controlled Access to User-Specific Information

📱 7. Responsive User Interface

The application is designed with responsive layout techniques to support multiple screen sizes.

- Modern and Clean Interface
- Responsive Navigation and Layout Components
- Mobile-Friendly Forms
- Interactive Buttons and Controls
- Organized Content Sections
- Accessible Flight and Booking Information

Individual pages should be tested at different viewport sizes before deployment.


💻 Technology Stack

Technology| Purpose
HTML5| Semantic structure and content organization
CSS3| Styling, layout, and responsive design
JavaScript| Client-side interactivity
PHP 8+| Server-side logic and request processing
MySQL| Relational database management
Apache| Local web server
XAMPP| Local development environment
phpMyAdmin| Database administration
Leaflet.js| Interactive map rendering
OpenStreetMap| Geographic and mapping information
AirLabs API| External flight status information
PHPMailer| Optional SMTP-based booking notification integration
Composer| PHP dependency management
Visual Studio Code| Source code development
Git & GitHub| Version control and repository management


🔄 Application Workflow

The primary demonstration booking process follows a structured sequence:

Flight Search → Flight Selection → Seat Selection → Passenger Information → Save Demo Booking → View Journey Details

Users can search for flight options, choose a desired itinerary, enter passenger information, and save their selected journey to the application database.

Saved journeys can subsequently be accessed through the user's booking history and itinerary management pages.

The flight tracking module functions independently using external flight status data when available.


🏗️ System Architecture

BookAFlight follows a database-driven web application architecture consisting of three primary layers.

Presentation Layer

Developed using HTML, CSS, and JavaScript.

Responsible for displaying flight search forms, booking interfaces, passenger details, dashboards, and interactive maps.

Application Layer

Developed using PHP.

Handles user authentication, input validation, flight search processing, passenger management, booking operations, and communication with external services.

Data Layer

Powered by MySQL.

Stores application information such as user accounts, flight records, passenger profiles, bookings, and saved journeys.

Architecture Overview:

User / Web Browser → Frontend Interface → PHP Application Logic → MySQL Database

External services, including flight status APIs, communicate with dedicated application modules when configured.


⚙️ Installation & Setup

System Requirements

- XAMPP with PHP 8.0 or later
- Apache Web Server
- MySQL or compatible database server
- A modern web browser
- Visual Studio Code or another code editor
- Composer for dependency installation

Step 1 — Download the Repository

Clone the repository or download the project source code as a ZIP archive.

Place the project folder inside the XAMPP "htdocs" directory.

Example location:

"C:\xampp\htdocs\BookAFlight"

Step 2 — Start the Local Server

Open XAMPP Control Panel and start the following services:

- Apache
- MySQL

Step 3 — Configure the Database

Open phpMyAdmin using:

"http://localhost/phpmyadmin/"

Create the database required by the application and import the SQL schema files supplied with the project.

Configure the database connection settings in the applicable PHP configuration files, including "config.php" and "db.php".

Step 4 — Install PHP Dependencies

If the repository includes Composer dependency files, open a terminal in the project directory and run:

"composer install"

This installs the required PHP packages using the project's Composer configuration.

Step 5 — Configure Optional Services

For external flight status functionality, configure a valid AirLabs API key.

For booking email notifications, configure PHPMailer with valid SMTP credentials.

These integrations require additional configuration and should not expose credentials in the public repository.

Step 6 — Run the Application

Open your browser and visit:

"http://localhost/BookAFlight/"

The application can now be accessed through the local XAMPP environment after the required database setup is complete.


📂 Core Project Modules

File| Description
"index.php"| Homepage and flight discovery
"booking.php"| Seat selection and demo booking processing
"journeys.php"| Saved journey management
"my-bookings.php"| Personal booking history
"profile.php"| User profile management
"saved-passengers.php"| Saved passenger information
"flight-status.php"| External flight status lookup
"booking-share.php"| Demonstration itinerary sharing
"boarding-pass.php"| Demo boarding pass generation
"help-center.php"| Frequently asked questions and help resources
"config.php"| Application configuration and database connectivity
"db.php"| Database and authentication-related functionality

The repository may contain additional supporting files, scripts, stylesheets, and assets depending on the current development version.


🔐 Security & Data Management

BookAFlight incorporates security-oriented programming practices within its supported modules.

Key considerations include:

- Password protection and account authentication
- Server-side validation of submitted information
- Parameterized database queries
- CSRF protection for applicable forms
- Session-based access restrictions
- User-specific database access controls
- Controlled file upload handling
- Security considerations for private configuration files

Sensitive information such as database passwords, SMTP credentials, and API keys must never be committed to a public GitHub repository.

Additional security assessment is required before production deployment.


📈 Future Development Roadmap

BookAFlight is designed to support further expansion into an advanced travel management platform.

Development Area| Planned Enhancement
Flight Integration| Real flight offers and availability revalidation
Booking Management| Advanced booking lifecycle and confirmation states
Seat Management| Smart seat locking and seat-hold expiration
Payments| Online payment gateway sandbox
Refunds| Cancellation, rescheduling, and refund tracking
Recommendations| Smart flight recommendation engine
Fare Analysis| Flight price calendar and price comparison
Notifications| Automated booking and travel alerts
Travel Assistance| Flight disruption guidance
Administration| Advanced dashboard and analytical reporting

These items represent proposed enhancements rather than completed production capabilities.


📌 Project Status

Academic Prototype — Active Development

BookAFlight provides a foundation for flight discovery, local demonstration bookings, passenger and journey management, and travel assistance.

The project combines database-driven application functionality with optional third-party integrations.

Certain integrations require additional configuration, and individual modules may require further integration and testing.

The project is intended for academic demonstration and continued development.


⚠️ Important Disclaimer

BookAFlight is an academic demonstration application.

Saved journeys and demo booking records do not constitute real airline reservations, confirmed airline seats, issued airline tickets, or valid Passenger Name Records (PNRs).

Prices and seat selections in demonstration workflows are illustrative and do not represent guaranteed live airline inventory.

Flight status information, when configured, is provided by an external API and should be verified with official airline or airport sources.

The application does not currently provide commercial airline ticket issuance or production payment processing.

GitHub hosts the source code, not the running PHP application. To run BookAFlight, a compatible PHP web server and MySQL database environment are required.


✈️ BookAFlight

A smarter approach to flight discovery, journey planning, and travel management.

Search Smarter. Plan Better. Travel Easier.
