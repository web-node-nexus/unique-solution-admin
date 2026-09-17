import Constants from 'expo-constants';

/**
 * Expo Go (SDK 53+) no longer ships Android remote push via expo-notifications.
 * Importing the module there throws — so we only load it in real builds.
 */
export function isExpoGo(): boolean {
  return Constants.appOwnership === 'expo';
}

type NotificationsModule = typeof import('expo-notifications');

let cached: NotificationsModule | null | undefined;

export function getNotifications(): NotificationsModule | null {
  if (cached !== undefined) return cached;
  if (isExpoGo()) {
    cached = null;
    return null;
  }
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    cached = require('expo-notifications') as NotificationsModule;
  } catch {
    cached = null;
  }
  return cached;
}
