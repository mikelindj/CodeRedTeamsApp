import React, { useState, useEffect } from 'react';
import {
  FluentProvider,
  webLightTheme,
  Button,
  Card,
  CardHeader,
  Dialog,
  DialogSurface,
  DialogTitle,
  DialogBody,
  DialogActions,
  DialogContent,
  Input,
  Label,
  Spinner,
  MessageBar,
  MessageBarBody,
  MessageBarTitle,
  makeStyles,
  tokens,
  Text,
  Title1,
} from '@fluentui/react-components';
import { Dismiss24Regular, Send24Regular } from '@fluentui/react-icons';

const SHAREPOINT_SITE_URL = "https://acsacademysg.sharepoint.com/sites/allstaff";
const LIST_NAME = "Code Red Locations";
const WEBHOOK_URL = "https://default6dff32de1cd04ada892b2298e1f616.98.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/bc5cce4c0d4f42e9bd42cf926663b8be/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=qy1Mu92ciK4-IZBKVTIIVgBm9d6ZWbcnZqD35YoqUjY";

// Backend API URL
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://parents.acsacademy.edu.sg';

// Custom professional theme with red accent
const customTheme = {
  ...webLightTheme,
  colorBrandForeground1: '#C8102E', // Professional red
  colorBrandForeground2: '#A00D26',
  colorBrandBackground: '#C8102E',
  colorBrandBackground2: '#E8F4F8',
};

const useStyles = makeStyles({
  container: {
    maxWidth: '1200px',
    margin: '0 auto',
    padding: '16px',
    minHeight: '100vh',
    backgroundColor: 'rgb(0, 32, 92)',
    '@media (min-width: 768px)': {
      padding: '24px',
    },
  },
  header: {
    marginBottom: '24px',
    padding: '20px',
    backgroundColor: 'rgb(172, 0, 39)',
    borderRadius: tokens.borderRadiusLarge,
    boxShadow: tokens.shadow4,
    color: 'white',
    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    '@media (min-width: 768px)': {
      padding: '24px 32px',
    },
  },
  headerTitle: {
    color: 'white',
    marginBottom: '8px',
    fontSize: '28px',
    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    '@media (min-width: 768px)': {
      fontSize: '36px',
    },
  },
  grid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(3, 1fr)',
    gap: '12px',
    '@media (min-width: 768px)': {
      gridTemplateColumns: 'repeat(3, 1fr)',
      gap: '20px',
    },
    '@media (min-width: 1024px)': {
      gridTemplateColumns: 'repeat(4, 1fr)',
    },
  },
  card: {
    cursor: 'pointer',
    transition: 'all 0.2s ease',
    minHeight: '100px',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
    backgroundColor: tokens.colorNeutralBackground1,
    border: `2px solid ${tokens.colorNeutralStroke2}`,
    ':hover': {
      transform: 'translateY(-4px)',
      boxShadow: tokens.shadow16,
      border: `2px solid ${customTheme.colorBrandForeground1}`,
    },
    '@media (min-width: 768px)': {
      minHeight: '140px',
    },
  },
  cardHeader: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    flex: 1,
    width: '100%',
  },
  cardTitle: {
    margin: 0,
    fontSize: '18px',
    fontWeight: 600,
    color: tokens.colorNeutralForeground1,
    textAlign: 'center',
    width: '100%',
    lineHeight: '1.3',
    '@media (min-width: 768px)': {
      fontSize: '20px',
    },
    '@media (min-width: 1024px)': {
      fontSize: '24px',
    },
  },
  emptyState: {
    textAlign: 'center',
    padding: '60px 20px',
    backgroundColor: tokens.colorNeutralBackground1,
    borderRadius: tokens.borderRadiusLarge,
  },
  loadingContainer: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '400px',
  },
  button: {
    minHeight: '48px',
    fontSize: '16px',
    padding: '12px 24px',
    fontWeight: 600,
    '@media (min-width: 768px)': {
      minHeight: '52px',
      fontSize: '32px',
      padding: '14px 32px',
    },
  },
  buttonPrimary: {
    backgroundColor: customTheme.colorBrandForeground1,
    color: 'white',
    ':hover': {
      backgroundColor: customTheme.colorBrandForeground2,
    },
  },
  dialogButtonCancel: {
    minHeight: '48px',
    fontSize: '16px',
    padding: '12px 24px',
    fontWeight: 600,
    width: '100%',
    '@media (min-width: 480px)': {
      width: 'auto',
    },
    '@media (min-width: 768px)': {
      minHeight: '52px',
      fontSize: '32px',
      padding: '14px 32px',
    },
  },
  dialogButtonSubmit: {
    minHeight: '48px',
    fontSize: '16px',
    padding: '12px 24px',
    fontWeight: 600,
    backgroundColor: customTheme.colorBrandForeground1,
    color: 'white',
    width: '100%',
    ':hover': {
      backgroundColor: customTheme.colorBrandForeground2,
    },
    '@media (min-width: 480px)': {
      width: 'auto',
    },
    '@media (min-width: 768px)': {
      minHeight: '52px',
      fontSize: '32px',
      padding: '14px 32px',
    },
  },
  dialogSurface: {
    width: '90%',
    maxWidth: '500px',
    '@media (min-width: 768px)': {
      width: '100%',
    },
  },
  input: {
    minHeight: '48px',
    fontSize: '16px',
    width: '100%',
    '@media (min-width: 768px)': {
      minHeight: '52px',
      fontSize: '32px',
    },
  },
  dialogActions: {
    display: 'flex',
    flexDirection: 'column',
    gap: '12px',
    '@media (min-width: 480px)': {
      flexDirection: 'row',
    },
  },
  dialogButton: {
    width: '100%',
    '@media (min-width: 480px)': {
      width: 'auto',
    },
  },
  closeButton: {
    position: 'absolute',
    top: '-10px',
    right: '-10px',
    zIndex: 1,
  },
  dialogSurfaceContainer: {
    position: 'relative',
  },
});

function App() {
  const styles = useStyles();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    fetchSharePointList();
  }, []);

  const fetchSharePointList = async () => {
    try {
      setLoading(true);
      setError(null);

      // Check if running in Teams
      const isTeams = window.microsoftTeams !== undefined;
      
      if (!isTeams) {
        throw new Error('This app must be run within Microsoft Teams. Please open it from Teams.');
      }

      // Call backend API which handles Azure AD authentication
      if (!API_BASE_URL) {
        throw new Error(
          'Backend API URL not configured.\n\n' +
          'Please set VITE_API_BASE_URL environment variable or deploy a backend API.\n\n' +
          'The backend API should:\n' +
          '1. Use Azure AD client credentials flow\n' +
          '2. Have Sites.Read.All (Application) permission\n' +
          '3. Expose an endpoint: /api/sharepoint/list\n\n' +
          'See deployment documentation for backend setup instructions.'
        );
      }
      
      const apiUrl = `${API_BASE_URL}/api/getSharePointList.php`;
      console.log('📡 Calling backend API:', apiUrl);

      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: response.statusText }));
        throw new Error(errorData.error || `Error ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      
      if (data.items && Array.isArray(data.items)) {
        setItems(data.items);
        console.log(`✅ Loaded ${data.items.length} items from SharePoint via backend API`);
      } else {
        throw new Error('Unexpected response format from API');
      }

    } catch (err) {
      console.error('Error fetching SharePoint list:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const formatCardTitle = (title) => {
    // Remove text before and including the "-" character
    const dashIndex = title.indexOf('-');
    if (dashIndex !== -1) {
      return title.substring(dashIndex + 1).trim();
    }
    // If no "-" found, return the original title
    return title;
  };

  const handleCardClick = async (item) => {
    if (isSubmitting) return; // Prevent multiple submissions

    setIsSubmitting(true);

    try {
      // Get user context from Teams SDK to get principal user name
      let userPrincipalName = '';
      if (window.microsoftTeams) {
        try {
          const context = await window.microsoftTeams.app.getContext();
          userPrincipalName = context.user?.userPrincipalName || context.user?.loginHint || '';
          console.log('User principal name:', userPrincipalName);
        } catch (err) {
          console.warn('Could not get user context:', err);
        }
      }

      // Create payload
      const payload = {
        location: item.title,
        'date-time': new Date().toISOString(),
        userPrincipalName: userPrincipalName
      };

      const response = await fetch(WEBHOOK_URL, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          // Add authentication header if required by Power Automate
          // 'Authorization': 'Bearer YOUR_TOKEN_HERE',
        },
        body: JSON.stringify(payload)
      });
      
      if (response.ok) {
        alert('Location submitted successfully!');
      } else if (response.status === 401) {
        throw new Error('Authentication failed. Please check Power Automate flow settings or add authentication headers.');
      } else {
        const errorText = await response.text().catch(() => '');
        throw new Error(`HTTP error! status: ${response.status}${errorText ? ` - ${errorText}` : ''}`);
      }
    } catch (err) {
      console.error('Error sending to webhook:', err);
      const errorMessage = err.message.includes('401') || err.message.includes('Authentication')
        ? 'Authentication required. Please configure Power Automate flow to allow anonymous access or add authentication.'
        : 'Error submitting location. Please try again.';
      alert(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <FluentProvider theme={customTheme}>
      <div className={styles.container}>
        {/* Header */}
        <div className={styles.header}>
          <Title1 className={styles.headerTitle}>ACS(A) Code Red</Title1>
          <Text size={400} style={{ color: 'white', opacity: 0.9, fontSize: '16px' , display:'block' }}>
            {items.length} locations loaded
          </Text>
        </div>

        {/* Error Message */}
        {error && (
          <MessageBar intent="error" style={{ marginBottom: '20px' }}>
            <MessageBarTitle>Error</MessageBarTitle>
            <MessageBarBody>
              {error}
              <Button 
                appearance="primary" 
                onClick={fetchSharePointList}
                className={styles.button}
                style={{ marginLeft: '20px' }}
              >
                Retry
              </Button>
            </MessageBarBody>
          </MessageBar>
        )}

        {/* Loading State */}
        {loading && (
          <div className={styles.loadingContainer}>
            <Spinner label="Loading SharePoint list..." size="large" />
          </div>
        )}

        {/* Cards Grid */}
        {!loading && !error && (
          <>
            {items.length > 0 ? (
              <div className={styles.grid}>
                {items.map(item => (
                  <Card
                    key={item.id}
                    className={styles.card}
                    onClick={() => handleCardClick(item)}
                    style={{ 
                      opacity: isSubmitting ? 0.6 : 1,
                      cursor: isSubmitting ? 'not-allowed' : 'pointer',
                      pointerEvents: isSubmitting ? 'none' : 'auto'
                    }}
                  >
                    <CardHeader
                      className={styles.cardHeader}
                      header={
                        <Text className={styles.cardTitle}>{formatCardTitle(item.title)}</Text>
                      }
                    />
                  </Card>
                ))}
              </div>
            ) : (
              <div className={styles.emptyState}>
                <Text>No items found in the list</Text>
              </div>
            )}
          </>
        )}

      </div>
    </FluentProvider>
  );
}

export default App;
