<div align="center">
  <img src="Pic/TextLogo.png" alt="AUchive Logo" width="280">

  # AUchive

  **An integrated web-based platform for hybrid chat and narrative Alternate Universe (AU) content creation and reading.**
</div>

AUchive combines all AU writing needs in one website so writers do not need to use several different applications to compose a story. Writers can create conversations in the form of bubble chats and add narrative story content on the same page, upload supporting images, and complete the entire writing-to-publishing process in one integrated system. Meanwhile, readers can search, bookmark, and read both narrative stories and bubble chat stories seamlessly in one place.

## Table of Contents

1. [Features](#features)
2. [Actors](#actors)
3. [System Flow](#system-flow)
4. [Folder Structure](#folder-structure)
5. [Tech Stack](#tech-stack)
6. [Database Management System](#database-management-system)
7. [Contributors and Role Division](#contributors-and-role-division)

## Features

- **Authentication System**: Secure registration, login, session validation, and account settings (password/email updates) for guests, readers, writers, and administrators.
- **Story Creation Suite**: Form to add story cover images, titles, descriptions, genres, and tags.
- **Modular Chapter Editor**: Integrated writing space allowing authors to compose chapter summaries and dynamically insert narrative text blocks or interactive roomchat previews in any sorting order.
- **Customizable Bubble Chat Builder**: Chat customizer allowing authors to pick positions (left/right), format timestamps, write messages, upload custom bubble images, change bubble background colors, choose theme presets (WhatsApp Dark Mode or iMessage Light Mode), and customize sender details (custom names/avatars) for group chat simulations.
- **Interactive Reading Layout**: Seamless reader mode presenting hybrid story chapters, displaying narrative blocks and roomchats as real-time interactive previews side-by-side.
- **User Library / Bookmark Manager**: Custom list for users to track, bookmark, organize, and quickly access stories they follow.
- **User Engagement**: Interactive comments section on chapters and a follow system for authors to receive updates.
- **Notification Inbox**: Instant notifications for users when followed authors publish new chapters, receive likes, or gain new followers.
- **System Settings & Management**: Support tools for account deletion, FAQ guidelines, and help centers.
- **Moderation Dashboard (Admin)**: Administrative area for tracking reports, managing content, warning users, and deleting posts.

## Actors

| Actor | Responsibilities |
|---|---|
| **Guest / Visitor** | Accesses the landing page, searches for stories, reads story previews and public reading pages, accesses help guides, and registers/logs in to unlock features. |
| **Reader** | Views the homepage feed, searches for stories, likes chapters, comments on stories, views user profiles, follows favorite authors, receives update notifications, and manages their reading library. |
| **Writer / Author** | Manages stories (drafts, published, archived), creates chapters, uses the bubble chat builder to construct visual chats, manages narrative/chat blocks, sets themes, and previews stories. |
| **Admin / System** | Accesses the administrator dashboard, monitors system stats, handles user reports, moderates malicious contents, warns or bans users, and maintains database settings. |

## System Flow

1. **Manage Authentication (1.0)**  
   Users submit registration or login credentials. The system validates them against the **User Data Store (D1)** and returns session tokens and states.

2. **Manage Story and Chapter Creation (2.0)**  
   Writers submit story details (cover, metadata) and chapters. The system processes narratives and structure blocks, saving them into **Story Data Store (D2)**, **Chapter Data Store (D3)**, and **Chapter Block Data Store (D4)**.

3. **Manage Bubble Chat Customization (3.0)**  
   Writers customize chat theme cards, positions, timestamps, and upload media. The system saves roomchat metadata and individual chat messages in **Roomchat Data Store (D5)** and **Bubble Data Store (D6)**.

4. **Manage Reader Interactions (4.0)**  
   Readers view, search, comment, like, or follow. The system updates view counts and records interactions in **Interaction Data Store (D7)** (comments, likes, followers) and **Library Data Store (D8)**.

5. **Manage Moderation and Reports (5.0)**  
   Users submit reports on malicious content. Admins request lists of pending reports and submit decisions (warnings, deleting posts, or banning accounts) through the **Report Data Store (D9)**.

## Folder Structure

```text
AUchive/
├── Detstory.php              # Story detail and chapter list page
├── Editor.php                # Chapter text and bubblechat block editor
├── Etmin.php                 # Admin dashboard page
├── Guide.php                 # Interactive flow guide and help center
├── Library.php               # User library (reading lists/bookmarks)
├── Notification.php          # Notification inbox page
├── Profile.php               # Current user profile dashboard
├── Readingpage.php           # Chapter reader with integrated roomchat previews
├── Setting.php               # User settings page
├── bubblechat.php            # Chat bubble customizer and builder
├── flowguide.php             # System guide flow data controller
├── homepage.php              # Main search, banner, and recommendations page
├── profile_person.php        # Other user profile viewing page
├── search_result.php         # Story and user search results page
├── setup_admin.php           # Initial database setup and administration utility
├── auchive1.sql              # Database schema dump and default parameters
├── Pic/                      # Logos, icons, banners, and default graphics
│   ├── Logo.png              # AUchive main logo
│   ├── PP kosongan.jpg       # Default empty profile picture placeholder
│   └── cover-utama.jpg       # Main homepage hero cover image
├── Uploads/                  # User uploads directory
└── src/                      # System modular source code
    ├── Admin/                # Admin-specific assets and logic
    ├── BubbleChat/           # Chat customization CSS, JS, and backend APIs
    ├── Chapter/              # Chapter rendering, saving, and deletion code
    ├── Comment/              # Comment management system
    ├── Core/                 # Database configuration and globally shared JS helpers
    ├── Guide/                # Modular logic for flow guides
    ├── Library/              # Bookmarks and custom libraries logic
    ├── Notification/         # Notification backend APIs
    ├── Report/               # Report submission and admin tracking
    ├── Story/                # Story metadata, cover uploads, and story management APIs
    └── User/                 # Login, registration, and user profile management
```

## Tech Stack

<div align="center">

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![Cloudinary](https://img.shields.io/badge/Cloudinary-3448C5?style=for-the-badge&logo=cloudinary&logoColor=white)

</div>

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 8 and AJAX-style JSON endpoints |
| Database | MySQL with SQL |
| Local Server | Apache through XAMPP|
| Database Access | PHP PDO (PHP Data Objects) with prepared statements |
| CDN Storage | Cloudinary API integration for user-uploaded cover images and custom avatars |


## Database Management System

AUchive uses **MySQL** as its relational DBMS. Database connections are handled securely by PHP through **PDO** (configured in [database.php](file:///c:/xampp/htdocs/AUchive%20Rev/src/Core/PHP/database.php)), while the database definition and baseline dump is available in [auchive1.sql](file:///c:/xampp/htdocs/AUchive%20Rev/auchive1.sql).

| Table | Purpose |
|---|---|
| `users` | Stores user credentials, profiles (avatars/banners/bios), settings, and registration details. |
| `stories` | Stores story metadata (title, description, cover image, status, genre, tags, and creator ID). |
| `chapters` | Stores chapters connected to stories (chapter title, summary, order, and status). |
| `chapter_blocks` | Connects specific content blocks (text or roomchat previews) inside a chapter. |
| `roomchats` | Stores roomchat headers (theme, contact name, custom backgrounds, and custom avatars). |
| `bubbles` | Stores individual chat bubble messages (message text, alignment, time label, custom sender name/avatar, custom bubble image, and specific colors). |
| `comments` | Stores discussions and feedback on stories. |
| `followers` | Maps user follower and following relationships. |
| `library` / `library_stories` | Tracks user reading lists and bookmarks. |
| `story_likes` / `chapter_likes` | Monitors user engagement through likes. |
| `reports` | Manages reported contents and users for moderation reviews. |


Main relationships:
- One user can create many stories, comments, likes, and followers.
- One story can contain multiple chapters, likes, and comments.
- One chapter is divided into multiple sequential blocks (narratives or roomchats).
- One roomchat block has many bubbles containing sorted conversations.
- Deleting parent records cascades to related table rows using foreign key definitions.

## Contributors and Role Division

All contributors worked as **Fullstack Developers**, covering frontend, backend, database integration, testing, and documentation. Each member also had a main specialization to keep the development process more focused and organized.

| Contributor | Student ID | Role | Main Responsibilities |
|---|---|---|---|
| [Aleiandra Carrissa Irawan]| F1D02410034 | Fullstack Developer - Reader Side Specialist | Developing reader-focused features, reading page layouts, interactive reading preview modes, user library/bookmarking, and reader-related database structures. |
| [Raissa Bunga Astrella] | F1D02410087 | Fullstack Developer - Writer Side Specialist | Developing story creation UIs, bubble chat customizer/builder, chapter text and block editors, story draft/publishing workflows, and writer-related preview utilities. |
| [Meisya Ananda Puteri]| F1D02410072 | Fullstack Developer - System / Platform Side Specialist | Developing authentication (login/register), notifications, settings, user profiles, database schema integration, Cloudinary API integration, and admin dashboard operations. |

## Author Notes
This project was developed as part of the final project requirement for the Web Programming course. It presents the implementation of AUchive, a web-based information system designed to bridge narrative writing and bubble chat storytelling in a clean and integrated platform, improving the overall workflow of both AU authors and readers.
