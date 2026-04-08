interface NetworkErrorProps {
  message?: string;
  onRetry?: () => void;
}

/** Standard network/API error display with optional retry action. */
export default function NetworkError({ message, onRetry }: NetworkErrorProps) {
  return (
    <div className="flex flex-col items-center justify-center py-10 text-center px-4">
      <span className="text-3xl mb-3" role="img" aria-hidden="true">⚠️</span>
      <p className="text-sm font-medium text-gray-700 mb-1">
        {message ?? 'Ocorreu um erro ao carregar os dados.'}
      </p>
      <p className="text-xs text-gray-400 mb-4">Verifique a conexão ou tente novamente.</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="px-4 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Tentar novamente
        </button>
      )}
    </div>
  );
}
