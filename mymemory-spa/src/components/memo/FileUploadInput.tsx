import { useRef, useState } from 'react';
import clsx from 'clsx';

interface Props {
  accept: string;
  maxSizeMb?: number;
  onChange: (file: File | null) => void;
  label?: string;
  disabled?: boolean;
}

function formatSize(bytes: number): string {
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function FileUploadInput({ accept, maxSizeMb, onChange, label, disabled }: Props) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);
  const [sizeError, setSizeError] = useState<string | null>(null);
  const [dragging, setDragging] = useState(false);

  function handleFile(f: File | null) {
    setSizeError(null);
    if (!f) { setFile(null); onChange(null); return; }

    if (maxSizeMb && f.size > maxSizeMb * 1024 * 1024) {
      setSizeError(`Arquivo muito grande. Limite: ${maxSizeMb} MB.`);
      setFile(null);
      onChange(null);
      return;
    }

    setFile(f);
    onChange(f);
  }

  function onInputChange(e: React.ChangeEvent<HTMLInputElement>) {
    handleFile(e.target.files?.[0] ?? null);
  }

  function onDrop(e: React.DragEvent) {
    e.preventDefault();
    setDragging(false);
    handleFile(e.dataTransfer.files?.[0] ?? null);
  }

  return (
    <div className="space-y-1">
      {label && <p className="text-xs text-neutral-500">{label}</p>}

      <div
        onClick={() => !disabled && inputRef.current?.click()}
        onDragOver={(e) => { e.preventDefault(); if (!disabled) setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={clsx(
          'flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 cursor-pointer transition-colors',
          dragging ? 'border-indigo-500 bg-indigo-50' : 'border-neutral-200 hover:border-neutral-400 bg-white',
          disabled && 'opacity-50 cursor-not-allowed',
        )}
      >
        <svg className="w-8 h-8 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        {file ? (
          <div className="text-center">
            <p className="text-sm font-medium text-neutral-700 truncate max-w-xs">{file.name}</p>
            <p className="text-xs text-neutral-400">{formatSize(file.size)}</p>
          </div>
        ) : (
          <div className="text-center">
            <p className="text-sm text-neutral-500">Clique ou arraste um arquivo</p>
            {maxSizeMb && <p className="text-xs text-neutral-400">Máximo: {maxSizeMb} MB</p>}
          </div>
        )}
      </div>

      {sizeError && <p className="text-xs text-red-500">{sizeError}</p>}

      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        onChange={onInputChange}
        disabled={disabled}
      />

      {file && (
        <button
          type="button"
          onClick={() => { setFile(null); onChange(null); if (inputRef.current) inputRef.current.value = ''; }}
          className="text-xs text-neutral-400 hover:text-red-500 transition-colors"
        >
          Remover arquivo
        </button>
      )}
    </div>
  );
}
