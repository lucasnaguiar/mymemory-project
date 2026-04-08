import Skeleton from '../ui/Skeleton';

/** Placeholder card shown while memos are loading. */
export default function MemoCardSkeleton() {
  return (
    <div className="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col gap-3">
      {/* Header */}
      <div className="flex items-center gap-2">
        <Skeleton className="w-7 h-7 rounded-full shrink-0" />
        <Skeleton className="h-4 flex-1 rounded" />
      </div>

      {/* Body lines */}
      <Skeleton className="h-3 w-full rounded" />
      <Skeleton className="h-3 w-4/5 rounded" />
      <Skeleton className="h-3 w-3/5 rounded" />

      {/* Keywords */}
      <div className="flex gap-1">
        {[1, 2, 3].map(i => (
          <Skeleton key={i} className="h-5 w-16 rounded-full" />
        ))}
      </div>

      {/* Footer */}
      <div className="flex items-center justify-between pt-2 border-t border-gray-100">
        <Skeleton className="h-3 w-20 rounded" />
        <Skeleton className="h-3 w-12 rounded" />
      </div>
    </div>
  );
}
