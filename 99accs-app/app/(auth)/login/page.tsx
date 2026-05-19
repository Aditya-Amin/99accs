import { Suspense } from 'react';
import { LoginForm } from '@/components/auth/LoginForm';

export const metadata = { title: 'Login — 99Accs' };

export default function LoginPage() {
  return (
    <div className="col-xl-5 col-lg-6 col-md-8">
      <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
        <div className="modal-header">
          <h1 className="modal-title">Login</h1>
        </div>
        <div className="modal-body">
          <Suspense fallback={null}>
            <LoginForm />
          </Suspense>
        </div>
      </div>
    </div>
  );
}
