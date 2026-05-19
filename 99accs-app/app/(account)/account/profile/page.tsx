import ProfileClient from '@/components/account/ProfileClient';

export default function ProfilePage() {
  return (
    <div>
      <h2 className="title" style={{ marginBottom: 32 }}>My Profile</h2>
      <ProfileClient />
    </div>
  );
}
