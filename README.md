# ACS(A) Code Red Teams App

A Microsoft Teams application for form teachers to submit emergency location information during code red situations. The app displays classes from a SharePoint list and allows users to submit their current location via a Power Automate webhook.

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Components](#components)
- [Key Functions](#key-functions)
- [How It Works](#how-it-works)
- [Deployment](#deployment)
- [Backend Setup](#backend-setup)
- [Teams App Packaging](#teams-app-packaging)
- [Development](#development)

## 🎯 Overview

This application is a Microsoft Teams tab app built with React and Fluent UI that:

1. **Fetches class data** from a SharePoint list via a PHP backend API
2. **Displays classes** in a responsive card grid layout
3. **Allows location submission** via a modal dialog that sends data to a Power Automate webhook
4. **Runs entirely within Teams** with proper SDK integration

## 🏗️ Architecture

### Frontend (React + Vite)
- **Location**: `/src`
- **Framework**: React 18 with Fluent UI v9 components
- **Build Tool**: Vite
- **Deployment**: Azure Static Web Apps (via GitHub Actions)
- **URL**: `https://white-hill-0d8f99200.1.azurestaticapps.net`

### Backend API (PHP)
- **Location**: `/api/getSharePointList.php`
- **Hosting**: `https://parents.acsacademy.edu.sg/api/getSharePointList.php`
- **Purpose**: Fetches SharePoint list items using Microsoft Graph API
- **Authentication**: Azure AD client credentials flow (server-to-server)

### Teams Integration
- **Manifest**: `manifest.json`
- **SDK**: `@microsoft/teams-js` v2.47.2
- **Package**: `teams-app-package/` (for Teams org deployment)

### Data Flow

```
Teams App (Frontend)
    ↓ (HTTP GET)
Backend API (parents.acsacademy.edu.sg)
    ↓ (OAuth 2.0 Client Credentials)
Microsoft Graph API
    ↓
SharePoint List ("Form Teachers")
    ↓ (JSON Response)
Frontend displays cards
    ↓ (User clicks card, enters location)
Power Automate Webhook
```

## 🧩 Components

### Main Application Component (`src/App.jsx`)

The primary React component that manages the entire application state and UI.

#### State Management
- `items`: Array of SharePoint list items (classes)
- `loading`: Boolean indicating data fetch status
- `error`: Error message string (if any)
- `selectedItem`: Currently selected class item for location submission
- `location`: User-entered location string
- `isSubmitting`: Boolean indicating webhook submission status

#### UI Sections

1. **Header**
   - Displays "ACS(A) Code Red" title
   - Shows count of loaded classes
   - Styled with red background (`rgb(172, 0, 39)`) and white text

2. **Error Message Bar**
   - Displays errors with retry button
   - Uses Fluent UI `MessageBar` component

3. **Loading Spinner**
   - Shows while fetching SharePoint data
   - Uses Fluent UI `Spinner` component

4. **Cards Grid**
   - Responsive grid layout:
     - Mobile: 3 columns
     - Desktop: 4 columns
   - Each card represents a class from SharePoint
   - Cards are clickable and trigger the location modal

5. **Location Modal/Dialog**
   - Opens when a card is clicked
   - Contains:
     - Class name display
     - Location input field
     - Cancel and Submit buttons
   - Close button positioned at top-right

### Entry Point (`src/main.jsx`)

Initializes the React application and Microsoft Teams SDK:

- **Teams SDK Initialization**: Detects Teams context and initializes the SDK
- **React Rendering**: Mounts the App component to the DOM
- **Error Handling**: Gracefully handles cases where Teams SDK is unavailable

### Styling (`src/index.css`)

Global CSS reset and base styles:
- CSS reset for consistent rendering
- Body background: `rgb(0, 32, 92)` (dark blue)
- System font stack for native appearance

### Custom Theme (`src/App.jsx`)

Fluent UI theme customization:
- **Brand Color**: `#C8102E` (professional red)
- **Background**: Light theme with custom accent colors
- **Responsive Design**: Mobile-first approach with breakpoints

## 🔧 Key Functions

### `fetchSharePointList()`

Fetches class data from the backend API.

**Process:**
1. Checks if running in Teams context
2. Constructs API URL: `${API_BASE_URL}/api/getSharePointList.php`
3. Sends GET request with JSON headers
4. Parses response and updates `items` state
5. Handles errors and updates `error` state

**Error Handling:**
- Validates Teams context
- Checks API response status
- Provides user-friendly error messages
- Includes retry functionality

### `formatCardTitle(title)`

Formats class names for display by removing text before the first "-" character.

**Example:**
- Input: `"2024 - Class 1A"`
- Output: `"Class 1A"`

### `handleCardClick(item)`

Opens the location submission modal for a selected class.

**Actions:**
- Sets `selectedItem` state
- Resets `location` input
- Opens the dialog

### `handleCloseModal()`

Closes the location submission modal and resets related state.

### `handleSubmit(e)`

Submits location data to the Power Automate webhook.

**Process:**
1. Prevents default form submission
2. Validates location input
3. Creates payload with:
   - `class`: Selected class name
   - `location`: User-entered location
   - `date-time`: ISO timestamp
4. Sends POST request to webhook URL
5. Shows success/error alerts
6. Closes modal on success

**Error Handling:**
- Validates required fields
- Handles HTTP errors (401, etc.)
- Provides user feedback via alerts

## 🔄 How It Works

### Initial Load Flow

1. **Teams SDK Initialization** (`main.jsx`)
   - App loads in Teams context
   - SDK initializes and notifies Teams of success

2. **Component Mount** (`App.jsx`)
   - `useEffect` hook triggers `fetchSharePointList()`
   - Loading state is set to `true`

3. **Backend API Call**
   - Frontend sends GET request to `https://parents.acsacademy.edu.sg/api/getSharePointList.php`
   - Backend authenticates with Azure AD using client credentials
   - Backend calls Microsoft Graph API to fetch SharePoint list items
   - Backend returns JSON: `{ items: [...], count: N }`

4. **Data Processing**
   - Frontend receives and validates response
   - Items are sorted alphabetically
   - State updates trigger re-render

5. **UI Rendering**
   - Cards grid displays all classes
   - Each card shows formatted class name
   - Loading spinner disappears

### Location Submission Flow

1. **User Interaction**
   - User clicks on a class card
   - Modal opens with class name displayed

2. **Location Entry**
   - User types location in input field
   - Form validation ensures non-empty input

3. **Submission**
   - User clicks "Submit" button
   - `handleSubmit()` function executes
   - Payload is created with class, location, and timestamp

4. **Webhook Call**
   - POST request sent to Power Automate webhook URL
   - Webhook processes the data (e.g., creates SharePoint item, sends notification)

5. **Feedback**
   - Success: Modal closes, alert shown
   - Error: Alert shown with error message, modal remains open

### Responsive Design

The app adapts to different screen sizes:

- **Mobile (< 768px)**: 3-column grid, larger touch targets
- **Tablet (768px - 1024px)**: 3-column grid
- **Desktop (> 1024px)**: 4-column grid

All interactive elements (buttons, inputs) scale appropriately for touch and mouse input.

## 🚀 Deployment

### GitHub to Azure Static Web Apps

The app is automatically deployed to Azure Static Web Apps when code is pushed to the `main` branch.

#### CI/CD Pipeline (`.github/workflows/azure-static-web-apps-white-hill-0d8f99200.yml`)

**Trigger**: Push to `main` branch or pull request

**Steps:**
1. **Checkout Code**: Clones repository
2. **Setup Node.js**: Installs Node.js 18
3. **Install Dependencies**: Runs `npm ci`
4. **Build App**: Runs `npm run build` (creates `dist/` folder)
5. **Copy Config**: Copies `staticwebapp.config.json` if present
6. **Deploy**: Uses Azure Static Web Apps deploy action

**Configuration:**
- **App Location**: `/` (root)
- **Output Location**: `dist` (Vite build output)
- **API Location**: (empty - backend is separate)

**Secrets Required:**
- `AZURE_STATIC_WEB_APPS_API_TOKEN_WHITE_HILL_0D8F99200`: Azure deployment token

### Manual Deployment

If needed, you can manually trigger deployment:

```bash
# Build the app
npm run build

# The dist/ folder contains the deployable files
# Upload to Azure Static Web Apps via Azure Portal or CLI
```

## 🔌 Backend Setup

The backend API is hosted on `https://parents.acsacademy.edu.sg` and handles SharePoint data fetching.

### PHP Backend (`api/getSharePointList.php`)

**Purpose**: Fetches SharePoint list items using Microsoft Graph API with Azure AD authentication.

**Configuration Required:**

Set these environment variables on the server:

```bash
AZURE_CLIENT_ID=721e13ff-fce7-4d14-a576-53cacf015170
AZURE_CLIENT_SECRET=<your-client-secret>
AZURE_TENANT_ID=6dff32de-1cd0-4ada-892b-2298e1f61698
```

**Azure AD App Registration Requirements:**

1. **Application Permissions**:
   - `Sites.Read.All` (Microsoft Graph)

2. **Authentication**:
   - Client credentials flow (server-to-server)
   - No user interaction required

**API Endpoint:**

```
GET https://parents.acsacademy.edu.sg/api/getSharePointList.php
```

**Response Format:**

```json
{
  "items": [
    {
      "id": "1",
      "title": "2024 - Class 1A"
    },
    {
      "id": "2",
      "title": "2024 - Class 1B"
    }
  ],
  "count": 2
}
```

**Error Handling:**

- Returns HTTP 500 with error message if Azure AD configuration is missing
- Returns HTTP 500 with detailed error if Graph API calls fail
- Includes CORS headers for cross-origin requests

### Testing the Backend

Test scripts are available in the `/api` folder:

- `test-connection.php`: Tests basic connectivity
- `test-graph-api.php`: Tests each step of the Graph API flow

## 📦 Teams App Packaging

To deploy the app to Teams (as an org app or custom app), you need to create a ZIP package.

### Package Creation

Run the packaging script:

```bash
npm run package
```

This script:
1. Builds the app (`npm run build`)
2. Creates `teams-app-package/` directory
3. Copies `manifest.json` and built files
4. Includes app icons (if present)

### Package Contents

The `teams-app-package/` folder should contain:

```
teams-app-package/
├── manifest.json          # Teams app manifest
├── icon-outline.png       # 192x192 PNG (outline icon)
├── icon-color.png         # 192x192 PNG (color icon)
└── dist/                  # Built app files (optional, if hosting separately)
    ├── index.html
    └── assets/
```

### Creating the ZIP

1. Navigate to the `teams-app-package/` directory
2. Select all files (manifest.json, icons, dist folder)
3. Create a ZIP file (e.g., `code-red-app.zip`)

**Important**: The ZIP should contain the files directly, not a folder containing the files.

### Deployment to Teams

#### Option 1: Teams Admin Center (Org App)

1. Go to [Teams Admin Center](https://admin.teams.microsoft.com)
2. Navigate to **Teams apps** > **Manage apps**
3. Click **Upload new app**
4. Select your ZIP file
5. Review and approve the app
6. The app will be available to all users in your organization

#### Option 2: Teams Client (Custom App)

1. Open Microsoft Teams
2. Go to **Apps** > **Manage your apps** > **Upload a custom app**
3. Select your ZIP file
4. The app will be available in your personal apps

### Manifest Configuration

The `manifest.json` file contains:

- **App ID**: `721e13ff-fce7-4d14-a576-53cacf015170`
- **Content URL**: `https://white-hill-0d8f99200.1.azurestaticapps.net/index.html`
- **Valid Domains**: Includes Azure Static Web Apps and ACS Academy domains
- **Permissions**: `identity`, `messageTeamMembers`

**Before packaging**, ensure the `contentUrl` in `manifest.json` points to your deployed Azure Static Web Apps URL.

## 💻 Development

### Prerequisites

- Node.js 18+
- npm or yarn
- Microsoft Teams account (for testing)

### Local Development

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd CodeRedTeamsApp
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Start development server**:
   ```bash
   npm run dev
   ```

4. **Test in Teams**:
   - Use [ngrok](https://ngrok.com) or similar to expose localhost
   - Update `manifest.json` with your ngrok URL
   - Upload the app to Teams for testing

### Environment Variables

For local development, create a `.env` file:

```env
VITE_API_BASE_URL=https://parents.acsacademy.edu.sg
```

### Project Structure

```
CodeRedTeamsApp/
├── api/                      # Backend PHP files
│   ├── getSharePointList.php # Main API endpoint
│   ├── test-connection.php   # Connection test script
│   └── test-graph-api.php    # Graph API test script
├── src/                      # Frontend source code
│   ├── App.jsx              # Main application component
│   ├── main.jsx             # Entry point
│   └── index.css            # Global styles
├── public/                   # Static assets
├── dist/                     # Build output (generated)
├── teams-app-package/        # Teams app package (generated)
├── manifest.json            # Teams app manifest
├── package.json             # Dependencies and scripts
├── vite.config.js           # Vite configuration
└── README.md                # This file
```

### Available Scripts

- `npm run dev`: Start development server
- `npm run build`: Build for production
- `npm run package`: Build and create Teams app package

### Dependencies

**Production:**
- `@fluentui/react-components`: UI component library
- `@fluentui/react-icons`: Icon library
- `@microsoft/teams-js`: Teams SDK
- `react` & `react-dom`: React framework

**Development:**
- `vite`: Build tool and dev server
- `@vitejs/plugin-react`: React plugin for Vite

## 🔒 Security Notes

- **Client Secret**: Never commit Azure AD client secrets to the repository
- **Environment Variables**: Use environment variables for all sensitive configuration
- **CORS**: Backend API includes CORS headers for cross-origin requests
- **Teams Context**: App validates Teams context before making API calls

## 📝 License

This project is proprietary software for ACS Academy.

## 🤝 Support

For issues or questions, contact the development team or create an issue in the repository.

---

**Last Updated**: December 2024
**Version**: 1.0.3

