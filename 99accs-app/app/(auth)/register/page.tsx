import { Suspense } from 'react';
import { RegisterForm } from '@/components/auth/RegisterForm';

export const metadata = { title: 'Register — 99Accs' };

export default function RegisterPage() {
  return (
    <div className="col-xl-6 col-lg-7 col-md-9">
      <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
        <div className="modal-header">
          <h1 className="modal-title">Register</h1>
        </div>
        <div className="modal-body">
          <Suspense fallback={null}>
            <RegisterForm />
          </Suspense>
        </div>
      </div>
    </div>
  );
}
