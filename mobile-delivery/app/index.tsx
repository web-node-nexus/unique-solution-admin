import { Redirect } from 'expo-router';
import { useAuthStore } from '@/store/auth';

export default function Index() {
  const user = useAuthStore((s) => s.user);
  if (user) return <Redirect href="/(main)/pending" />;
  return <Redirect href="/login" />;
}
