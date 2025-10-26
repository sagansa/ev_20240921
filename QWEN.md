# Project Requirement Design: EV Charging Management System

## Table of Contents
1. [Introduction](#introduction)
2. [Current System Overview](#current-system-overview)
3. [System Architecture](#system-architecture)
4. [Functional Requirements](#functional-requirements)
5. [Non-Functional Requirements](#non-functional-requirements)
6. [Data Models](#data-models)
7. [User Interfaces](#user-interfaces)
8. [Future Enhancements](#future-enhancements)

## Introduction

This document outlines the requirements for the EV Charging Management System, a Laravel-based application designed to manage electric vehicle charging infrastructure. The system provides comprehensive tracking for charging locations, service providers, and charging session records.

## Current System Overview

The EV Charging Management System is currently built using the Laravel framework and serves as a comprehensive platform for managing electric vehicle charging infrastructure throughout Indonesia. The system allows users to:

- View and filter charging locations across different providers
- Access information about EV charging stations from various providers including PLN
- Track charging session records with detailed cost breakdowns
- View provider details and contact information

### Key Features Currently Implemented:

1. **Location Management**
   - Interactive map view of charging locations
   - Filtering by province, city, charging type, and power specifications
   - Integration with PLN charging locations (public utility provider)

2. **Provider Management**
   - Registration and management of charging service providers
   - Public and private provider access controls
   - Provider-specific details, pricing, and contact information

3. **Charging Session Tracking**
   - Detailed logging of charging sessions
   - Cost calculations including taxes and admin fees
   - Before/after kilometer tracking
   - kWh consumption measurement

## System Architecture

The application follows the Laravel MVC architecture with the following components:

- **Frontend**: Blade templates with Tailwind CSS styling
- **Backend**: Laravel PHP framework with MySQL database
- **Maps**: Interactive mapping functionality using OpenStreetMap (free alternative to Google Maps)
- **Authentication**: Laravel Sanctum API authentication
- **File Storage**: Image uploads for charging session verification

## Functional Requirements

### 1. Location Management System
**FR-001**: The system shall allow users to view all charging locations on an interactive map
- Priority: High
- Description: Users can see charging station locations with details about provider, availability, and specifications

**FR-002**: The system shall enable filtering of charging locations by multiple criteria
- Priority: High
- Description: Users can filter by province, city, charging type (AC/DC), power capacity, current type, and provider

**FR-003**: The system shall display detailed information for each charging location
- Priority: High
- Description: Information includes address, coordinates, provider, available chargers, and operational status

### 2. Provider Management System
**FR-004**: The system shall manage charging service providers
- Priority: High
- Description: Providers can be added, updated, and classified with different access levels

**FR-005**: The system shall display provider details including contact information
- Priority: Medium
- Description: Users can access provider website, app links, email, and pricing information

### 3. Charging Session Management
**FR-006**: The system shall record charging session details
- Priority: High
- Description: Each charging session includes date, start/finish times, kWh consumption, vehicle details, and kilometer readings

**FR-007**: The system shall calculate costs for each charging session
- Priority: High
- Description: Costs include energy consumption, taxes (street lighting tax, value-added tax), and admin fees

**FR-008**: The system shall support image verification for charging sessions
- Priority: Medium
- Description: Users can upload before and after images to verify charging sessions

### 4. Search and Filter Functionality
**FR-009**: The system shall allow searching for chargers by location name or provider
- Priority: Medium
- Description: Users can search for specific charging stations by name or provider

**FR-010**: The system shall enable sorting of charging stations
- Priority: Medium
- Description: Results can be sorted by location, province, city, power, provider, and usage metrics

### 5. YouTube Collection Feature
**FR-011**: The system shall provide a collection of YouTube videos related to EV
- Priority: Medium
- Description: Users can browse, search, and filter educational and informational EV-related videos with details like title, description, channel, category, and view count

**FR-012**: The system shall allow super-admin users to manage YouTube collection via Filament admin panel
- Priority: Medium
- Description: Super-admins can add, update, delete, and categorize YouTube videos in the collection exclusively through the Filament admin interface

### 6. Access Control Management
**FR-013**: The system shall implement role-based access control
- Priority: High
- Description: The system shall provide different access levels (user, admin, super-admin) with specific permissions for managing content and functionality

**FR-014**: The system shall restrict YouTube Collection management to super-admin users
- Priority: Medium
- Description: Only users with super-admin role can access the YouTube Collection CRUD functionality through the Filament admin panel

### 6. Location Search and Nearby Charger Discovery
**FR-013**: The system shall enable users to search for addresses using geocoding
- Priority: High
- Description: Users can input an address or location name and the system will convert it to geographic coordinates

**FR-014**: The system shall find charging stations near a specified location
- Priority: High
- Description: Using geographic coordinates, the system will identify and display charging stations within a specified radius of the location

**FR-015**: The system shall provide map visualization of nearby charging stations
- Priority: High
- Description: The system will display user location and nearby charging stations on an interactive map with markers and distance information

## Non-Functional Requirements

### Performance Requirements
**NFR-001**: The system shall handle up to 1000 concurrent users without performance degradation
- Priority: High

**NFR-002**: Map loading with charger locations shall complete within 3 seconds
- Priority: Medium

### Security Requirements
**NFR-003**: All charging session data shall be protected with encryption
- Priority: High

**NFR-004**: User authentication shall be required for sensitive operations
- Priority: High

### Availability Requirements
**NFR-005**: The system shall be available 99% of the time excluding scheduled maintenance
- Priority: High

### Scalability Requirements
**NFR-006**: The system shall support expansion to new geographical regions
- Priority: Medium

### SEO Requirements
**NFR-007**: The system shall implement proper SEO techniques
- Priority: Medium
- Description: The system shall include proper meta tags, structured data (JSON-LD), and a sitemap to improve search engine visibility

### Analytics Requirements
**NFR-008**: The system shall integrate with analytics tools
- Priority: Medium
- Description: The system shall include Google Analytics tracking to monitor user behavior and engagement metrics

## Data Models

### ChargerLocation
- id: UUID
- image: String (nullable)
- name: String
- provider_id: UUID (foreign key)
- location_on: Integer (1/3 - indicating location type)
- status: Integer
- description: Text (nullable)
- latitude: Float
- longitude: Float
- parking: Boolean
- province_id: UUID (foreign key)
- city_id: UUID (foreign key)
- address fields (district, subdistrict, postal code)
- user_id: Integer (foreign key)
- timestamps (created_at, updated_at, deleted_at)

### Charger
- id: UUID
- current_charger_id: UUID (foreign key)
- type_charger_id: UUID (foreign key)
- power_charger_id: UUID (foreign key)
- charger_location_id: UUID (foreign key)
- merk_charger_id: UUID (foreign key)
- timestamps (created_at, updated_at, deleted_at)

### Charge
- id: UUID
- vehicle_id: UUID (foreign key)
- date: Date
- charger_location_id: UUID (foreign key)
- charger_id: UUID (foreign key)
- km_now: Integer
- km_before: Integer
- start_charging_now: Integer
- finish_charging_now: Integer (nullable)
- finish_charging_before: Integer
- parking: Integer (default 0)
- kWh: Decimal (nullable)
- street_lighting_tax: Integer (default 0)
- value_added_tax: Integer (default 0)
- admin_cost: Integer (default 0)
- total_cost: Integer (default 0)
- image_start: String (nullable)
- image_finish: String (nullable)
- user_id: Integer (foreign key)
- timestamps (created_at, updated_at, deleted_at)

### Provider
- id: UUID
- name: String
- contact: String (nullable)
- email: String (nullable)
- web: String (nullable)
- google: String (nullable)
- ios: String (nullable)
- image: String (nullable)
- price: String (nullable)
- tax: String (nullable)
- admin_fee: String (nullable)
- status: Integer
- public: Integer
- timestamps (created_at, updated_at)

### Vehicle
- id: UUID
- brand_vehicle_id: UUID (foreign key)
- model_vehicle_id: UUID (foreign key)
- type_vehicle_id: UUID (foreign key)
- user_id: Integer (foreign key)
- name: String
- license_plate: String
- image: String (nullable)
- battery_capacity: Integer (nullable)
- timestamps (created_at, updated_at, deleted_at)

### YouTubeCollection
- id: UUID
- title: String
- video_id: String
- description: Text (nullable)
- thumbnail_url: String (nullable)
- channel_name: String (nullable)
- category: String (nullable)
- view_count: Integer (default 0)
- published_at: Timestamp (nullable)
- is_active: Boolean (default true)
- timestamps (created_at, updated_at, deleted_at)

**Access Control**: This model is managed exclusively through Filament admin panel by super-admin users

## User Interfaces

The system provides the following main interface views:

1. **Home Page**
   - Interactive map showing charging locations
   - Filter options for location search
   - Provider directory access

2. **Map View**
   - Detailed map with charging stations
   - Location filtering capabilities
   - Provider selection

3. **Provider Directory**
   - List of available charging providers
   - Search and sort functionality
   - Provider details and contact information

4. **Charger Directory**
   - Comprehensive list of charging stations
   - Filtering by location, power, current, type
   - Sorting by various criteria
   - Image verification for charging sessions

5. **PLN Charging Locations**
   - Dedicated view for PLN public charging stations
   - Filter by charging type and location category

6. **YouTube Collection**
   - Browse EV-related educational videos
   - Search and filter functionality by title, category, or channel
   - Video details page with embedded player
   - Accessible from main navigation menu

7. **Nearby Charger Finder**
   - Address search using geocoding
   - Map visualization of user location and nearby charging stations
   - List of charging stations with distance information
   - Radius selection for search area

## Future Enhancements

1. **Enhanced Analytics Dashboard**
   - Charging usage statistics
   - Provider performance metrics
   - Popular charging locations

2. **Mobile Application Integration**
   - Native iOS/Android apps
   - Real-time charging station availability
   - QR code scanning for station access

3. **Payment Integration**
   - Multiple payment method support
   - Subscription models for regular users
   - Loyalty programs for frequent users

4. **Charging Station Availability**
   - Real-time availability tracking
   - Reservation system for charging slots
   - Predictive maintenance alerts

5. **Integration with EV Manufacturers**
   - Direct vehicle compatibility checking
   - Recommended charging sessions
   - Battery health monitoring

6. **Advanced YouTube Collection Features**
   - User rating and review system for videos
   - Video recommendation based on user preferences
   - Playlist creation functionality
   - Content moderation workflow allowing editors to submit videos for super-admin approval

7. **Enhanced Location Features**
   - Turn-by-turn navigation to charging stations
   - Integration with popular navigation apps
   - Real-time traffic information for route planning

## Technical Specifications

- **Framework**: Laravel 10.x
- **Language**: PHP 8.1+
- **Database**: MySQL 8.0+
- **Frontend**: Blade templates, Tailwind CSS
- **Maps**: Interactive mapping functionality using OpenStreetMap (free alternative to Google Maps)
- **Authentication**: Laravel Sanctum
- **Authorization**: Role-based access control using Spatie Laravel-permission package
- **Image Storage**: Local storage with potential cloud integration
- **SEO**: Implementation of meta tags, structured data, and sitemap generation
- **Analytics**: Google Analytics integration for tracking user behavior and engagement
- **Admin Panel**: FilamentPHP for administrative interface

## Mapping Solution Changes

To avoid additional costs associated with Google Maps API usage, the system has been updated to use OpenStreetMap as the primary mapping solution. Key changes include:

1. Removal of Google Maps API dependencies
2. Implementation of Leaflet.js with OpenStreetMap tiles
3. Replacement of Google Maps links with OpenStreetMap equivalents
4. Migration of geocoding service from Google Maps Geocoding API to OpenStreetMap Nominatim
5. Removal of filament-google-maps package

## Map Component Refactor Plan

### Current Issues Identified:
1. Inconsistent styling between standard map and PLN map components
2. Redundant code in map implementations
3. Limited interactivity in current map views
4. Missing features for enhanced user experience

### Refactor Goals:
1. **Consistency**: Unify map components to share common styling, functionality, and structure
2. **Enhanced Interactivity**: Add new interactive features like:
   - Clustered markers for better performance with many locations
   - Enhanced popup information with images and detailed specs
   - Real-time location tracking with persistent user marker
   - Improved filtering controls with visual feedback
   - Animated transitions for smoother UX
3. **Performance Optimization**: 
   - Implement marker clustering for large datasets
   - Optimize rendering with virtual scrolling
   - Add lazy loading for images in popups
4. **Unified Component Structure**:
   - Create reusable map container component
   - Standardize control components
   - Implement consistent styling through CSS classes
   - Share common JavaScript utilities

### Implementation Plan:
1. **Create Shared Components**:
   - `resources/views/components/map/container.blade.php` - Main map container with standardized styling
   - `resources/views/components/map/controls.blade.php` - Unified filter controls
   - `resources/views/components/map/locate-button.blade.php` - Consistent location finder button
   - `resources/views/components/map/map-controls.blade.php` - Enhanced map interaction controls

2. **Standardize Styling**:
   - Consolidate CSS rules into common classes
   - Implement dark mode support consistently
   - Create responsive designs that work on all devices
   - Add animations for state transitions

3. **Enhance Functionality**:
   - Add marker clustering using Leaflet.markercluster
   - Implement smooth zooming and panning
   - Add keyboard navigation support
   - Improve error handling for geolocation

4. **Build Process Improvements**:
   - Configure Vite to automatically deploy builds to GitHub
   - Add build hooks for versioning and tagging
   - Implement automated deployment scripts

### New Interactive Features:
1. **Marker Clustering**: Automatically group nearby markers when zoomed out
2. **Enhanced Popups**: Show detailed information with images, charger specs, and usage stats
3. **Real-time Location**: Persistent user location marker with heading indication
4. **Advanced Filtering**: Multi-criteria filtering with visual indicators
5. **Routing Integration**: Directions to charging stations from user location
6. **Favorites System**: Allow users to save favorite charging locations
7. **Availability Status**: Real-time status indicators for charging stations
8. **Search Enhancement**: Fuzzy search with predictive text for locations

## Automated Deployment Configuration

To ensure built files are automatically sent to GitHub for easier hosting updates, the following configuration has been added to the build process:

1. **Vite Configuration Update**:
   - Added GitHub Actions workflow to automatically commit and push build files
   - Configured build hooks to trigger deployment after successful compilation
   - Set up version tagging for releases

2. **Deployment Script**:
   - Created `deploy.sh` script that runs after `npm run build`
   - Script automatically adds built files to git and commits with timestamp
   - Pushes changes to remote repository for immediate hosting update

3. **GitHub Actions Workflow**:
   - Added `.github/workflows/deploy.yml` for automated deployments
   - Workflow triggers on push to main branch or manual dispatch
   - Builds assets and pushes to GitHub Pages or designated branch

4. **Version Management**:
   - Integrated semantic versioning into build process
   - Automatic changelog generation based on commit messages
   - Release tagging for easy rollback and version tracking

This setup ensures that every time `npm run build` is executed, the resulting files are automatically committed and pushed to GitHub, making it much easier to update the hosting environment with the latest changes.

## Conclusion

The EV Charging Management System provides a comprehensive solution for managing electric vehicle charging infrastructure across Indonesia. The system currently offers robust location management, provider information, and charging session tracking capabilities. The modular architecture supports future enhancements to meet evolving market needs in the electric vehicle charging sector.

The planned map component refactor will significantly improve consistency between the standard map and PLN map views, while adding enhanced interactive features that will provide users with a much richer experience when exploring charging locations. The automated deployment configuration will streamline the update process, making it easier to deploy changes to the hosting environment.