'use client';
import { useUiStore } from '@/lib/store/uiStore';
import { IconClose } from '@/components/icons';
import { LoginPane } from './auth/LoginPane';
import { RegisterPane } from './auth/RegisterPane';
import { ForgotPasswordPane } from './auth/ForgotPasswordPane';

const TITLES = {
  login: 'Login',
  register: 'Register',
  'forgot-password': 'Reset password',
} as const;

export default function AuthModal() {
  const { authModalOpen, authModalMode, closeAuthModal } = useUiStore();
  if (!authModalOpen) return null;

  return (
    <div
      className="tg-modal__wrap fade show"
      style={{ display: 'block' }}
      onClick={(e) => { if (e.target === e.currentTarget) closeAuthModal(); }}
    >
      <div className="tg-modal-dialog">
        <div className="tg-modal-content">
          <div className="modal-header">
            <h1 className="modal-title">{TITLES[authModalMode]}</h1>
            <button type="button" className="btn-close tg-modal-close" onClick={closeAuthModal}>
              <IconClose />
            </button>
          </div>
          <div className="modal-body">
            {authModalMode === 'login' && <LoginPane />}
            {authModalMode === 'register' && <RegisterPane />}
            {authModalMode === 'forgot-password' && <ForgotPasswordPane />}
          </div>
        </div>
      </div>
    </div>
  );
}
