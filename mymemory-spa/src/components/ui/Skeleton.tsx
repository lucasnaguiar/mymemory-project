interface SkeletonProps {
  className?: string;
}

/** Generic pulsing skeleton block for loading states. */
export default function Skeleton({ className = '' }: SkeletonProps) {
  return (
    <div
      className={`animate-pulse bg-gray-200 rounded ${className}`}
      aria-hidden="true"
    />
  );
}
