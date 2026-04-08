import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useAcceptInvite } from '../hooks/useGroups';

type Phase = 'loading' | 'error' | 'success';

export default function GroupInviteAcceptPage() {
  const [searchParams] = useSearchParams();
  const navigate        = useNavigate();
  const token           = useMemo(() => (searchParams.get('token') ?? '').trim(), [searchParams]);

  const [phase, setPhase]   = useState<Phase>('loading');
  const [message, setMessage] = useState('');

  const { mutate: doAccept } = useAcceptInvite();

  useEffect(() => {
    if (!token) {
      setPhase('error');
      setMessage('Link inválido ou sem token de convite.');
      return;
    }

    doAccept(
      { token },
      {
        onSuccess: () => {
          setPhase('success');
          // Short redirect delay so user sees the success message
          setTimeout(() => navigate('/', { replace: true }), 1500);
        },
        onError: (err) => {
          setPhase('error');
          setMessage(err.message || 'Não foi possível aceitar o convite.');
        },
      },
    );
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  const loginHref = `/login?next=${encodeURIComponent(`/convite/grupo?token=${encodeURIComponent(token)}`)}`;

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
      <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 max-w-sm w-full text-center">
        <h1 className="text-xl font-bold text-gray-900 mb-4">Convite de grupo</h1>

        {phase === 'loading' && (
          <p className="text-sm text-gray-500">Verificando convite…</p>
        )}

        {phase === 'success' && (
          <div className="flex flex-col items-center gap-3">
            <span className="text-4xl">🎉</span>
            <p className="text-sm text-green-700 font-medium">Convite aceito com sucesso!</p>
            <p className="text-xs text-gray-400">Redirecionando para o início…</p>
          </div>
        )}

        {phase === 'error' && (
          <div className="flex flex-col items-center gap-4">
            <span className="text-4xl">⚠️</span>
            <p className="text-sm text-red-600">{message}</p>

            {message.toLowerCase().includes('login') || message.toLowerCase().includes('autenti') ? (
              <a
                href={loginHref}
                className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
              >
                Fazer login
              </a>
            ) : null}

            <Link to="/" className="text-sm text-indigo-600 hover:underline">
              Ir para o início
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
