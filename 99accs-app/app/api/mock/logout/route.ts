import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';

export async function POST() {
  const store = await cookies();
  // Clear every cookie our auth flow can set, regardless of which state the
  // user is in. Idempotent: safe to call even with no session.
  store.delete('99accs_token');
  store.delete('99accs_user');
  store.delete('99accs_reset_token');
  store.delete('99accs_reset_email');
  return NextResponse.json({ message: 'Logged out successfully.' });
}
