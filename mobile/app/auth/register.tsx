import { Redirect } from 'expo-router';

/** Password registration removed — first OTP login creates the account. */
export default function RegisterScreen() {
  return <Redirect href="/auth/login" />;
}
