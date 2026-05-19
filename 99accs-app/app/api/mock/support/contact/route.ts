import { NextResponse } from 'next/server';

export async function POST() {
  return NextResponse.json({ message: 'Message received. We will get back to you within 24 hours.' });
}
