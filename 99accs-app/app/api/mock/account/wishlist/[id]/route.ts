import { NextRequest, NextResponse } from 'next/server';
import { hasAuth } from '@/lib/auth/server';

export async function DELETE(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  return NextResponse.json({ message: 'Removed from wishlist.' });
}
