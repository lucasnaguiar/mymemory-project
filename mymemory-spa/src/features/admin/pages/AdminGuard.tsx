import { useNavigate } from 'react-router-dom';
import { useEffect } from 'react';
import { useMe } from '../../me/hooks/useMe';
import type { ReactNode } from 'react';

interface AdminGuardProps {
  children: ReactNode;
}

export default function AdminGuard({ children }: AdminGuardProps) {
  const navigate    = useNavigate();
  const { data: me, isLoading } = useMe();

  useEffect(() => {
    if (!isLoading && me && me.role !== 'admin') {
      navigate('/', { replace: true });
    }
  }, [me, isLoading, navigate]);

  if (isLoading) {
    return <div className="p-8 text-gray-400 text-sm">Carregando...</div>;
  }

  if (!me || me.role !== 'admin') return null;

  return <>{children}</>;
}
