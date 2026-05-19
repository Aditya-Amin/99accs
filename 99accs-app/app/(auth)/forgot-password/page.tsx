import { ForgotPasswordForm } from '@/components/auth/ForgotPasswordForm';

export const metadata = { title: 'Forgot Password — 99Accs' };

export default function ForgotPasswordPage() {
  return (
    <div className="col-xl-5 col-lg-6 col-md-8">
      <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
        <div className="modal-header">
          <h1 className="modal-title">Reset password</h1>
        </div>
        <div className="modal-body">
          <ForgotPasswordForm />
        </div>
      </div>
    </div>
  );
}
