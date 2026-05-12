# AUchive

## Short Description
AUchive is a web-based system used for entertainment purposes. This platform is designed to improve efficiency and effectiveness in reading and writing Alternate Universe (AU) content. In general, AU authors often need to switch between bubble chat maker applications and narrative writing platforms separately, which takes time when combining and publishing a story. Meanwhile, readers also need to move between different platforms to read narrative stories and bubble chat stories.

AUchive combines all AU writing needs in one website so writers do not need to use several different applications to compose a story. Writers can create conversations in the form of bubble chats and add narrative story content on the same page, upload supporting images, and complete the entire writing-to-publishing process in one integrated system.

---

## Project Title
**Development of an Integrated Web-Based System for Hybrid Chat and Narrative AU (Alternate Universe) Content Using Scrum Method**

---

## Overview
AUchive is a web-based platform for reading and writing Alternate Universe content. The system is designed to bring together narrative writing and bubble chat storytelling in one place. This reduces the need to use separate applications for story creation, editing, previewing, and publishing.

The platform allows users to create stories, customize chat themes, add narrative sections, upload cover images, manage drafts, and publish stories through a single integrated workflow.

---

## Team Members and Responsibilities

| No | Member Name | Role | Responsibilities |
|----|-------------|------|------------------|
| 1 | Aleiandra Carrissa Irawan | Reader Side | Develop reader-focused features, reading page flow, preview mode, and reader-related prototype to database structure. |
| 2 | Raissa Bunga Astrella | Writer Side | Develop story creation, bubble chat maker, chapter editor, preview, publish/draft flow, and writing-related features. |
| 3 | Meisya Ananda Puteri | System / Platform Side | Develop login, register, profile, notification, settings, database structure, API integration, and system-wide support features. |

### NIM Members Groups
- **Aleiandra Carrissa Irawan**: F1D02410034
- **Raissa Bunga Astrella**: F1D02410087
- **Meisya Ananda Puteri**: F1D02410072

Each sprint can be reviewed through testing and progress checking so every part is developed step by step.

---

## Methodology
This project uses the **Scrum** method.

### Scrum Workflow
- **Sprint duration**: 1 week per sprint
- **Progress review**: Every sprint ends with testing and evaluation
- **Task division**: Each member focuses on one main part of the system
- **Goal**: Build the system gradually, reduce confusion, and make integration easier

### Example Sprint Flow
- **Sprint 1**: UI prototype and database planning
- **Sprint 2**: Login, sign up, and profile structure
- **Sprint 3**: Reader page and homepage
- **Sprint 4**: Story creation and editor
- **Sprint 5**: Bubble chat theme and preview
- **Sprint 6**: Search, notification, and settings
- **Sprint 7**: Final testing and integration

---

## Technology Stack
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Local server**: XAMPP / Laragon
- **Design support**: Figma / Canva / Photoshop
- **Version control**: Git / GitHub

---

## Design System

### Color Palette
- **Primary Dark**: `#1C1C1C` | `rgb(28, 28, 28)`
- **Accent Yellow**: `#fff44f` | `rgb(255, 244, 79)`
- **White**: `#ffffff` | `rgb(255, 255, 255)`
- **Black**: `#000000`

### Fonts
- **Application name / logo**: Bitter
- **Landing page**: Lora
- **Main UI**: Bitter, Poppins

---

## Website Actors and Features

### 1. Guest / Visitor
**Features:**
- View landing page
- Search stories
- Read story preview and reading page
- Read about AUchive
- View contact person
- Log in
- Sign in / register
- Access write button as a prompt to register or log in

### 2. Reader
**Features:**
- View homepage
- Search stories
- Read story preview and reading page
- View story recommendations
- View other user profile
- Follow author
- Read notifications

### 3. Writer / Author
**Features:**
- Create new story
- Save story as draft
- Publish story
- Edit story
- Add new chapter
- Add cover, title, description, tag, and genre
- Create bubble chat content
- Customize bubble chat theme
- Preview story before publishing

### 4. System / Admin
**Features:**
- Manage users
- Manage content
- Maintain database
- Handle account settings
- Support website operations

---

## Sitemap

```text
AUchive
├── Homepage
│   ├── Menu Bar
│   │   ├── Log In
│   │   ├── Sign In / Register
│   │   ├── Account / Profile
│   │   ├── Notification
│   │   ├── Search
│   │   ├── Library
│   │   └── Logo
│   ├── Upper Banner
│   ├── Favorite Stories
│   ├── Recommended Stories
│   └── Lower Banner
│
├── Profile
│   ├── Banner
│   ├── Profile Photo
│   ├── Edit Profile
│   ├── Username
│   ├── Name
│   ├── Bio
│   ├── Followers
│   ├── Following
│   ├── Owned Stories
│   │   ├── Draft
│   │   ├── Publish
│   │   ├── Delete Story
│   │   └── Unpublish Story
│   └── Add Story
│
├── Notification
│   ├── Story Update Notification
│   ├── Like Notification
│   └── Followers Notification
│
├── Other User Profile
│   ├── Banner
│   ├── Profile Photo
│   ├── Follow Button
│   ├── Username
│   ├── Name
│   ├── Bio
│   ├── Followers
│   ├── Following
│   └── Draft Stories
│
├── Settings
│   ├── Account Settings
│   │   ├── Email
│   │   └── Password
│   ├── Help Center
│   │   ├── FAQ
│   │   └── Contact Email
│   ├── Log Out
│   └── Delete Account
│       ├── Username
│       ├── Password
│       └── Confirmation Yes / No
│
└── Story Creation
    ├── Publish or Draft
    ├── Cover
    ├── Title
    ├── Description
    ├── Tag
    ├── Genre
    ├── Next Button
    ├── Default Narrative
    ├── Chapter
    ├── Bubble Chat Builder
    ├── Chat Theme
    ├── Customize Theme
    ├── Chat History
    ├── Theme Type
    ├── Theme Color
    ├── Upload Background
    ├── Upload Profile Photo
    ├── Save
    ├── Chat Preview
    └── Reading Page Preview
