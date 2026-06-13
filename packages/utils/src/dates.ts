/**
 * Returns true if date range [aStart, aEnd) overlaps with [bStart, bEnd).
 * Used for rental booking overlap detection.
 *
 * Two ranges overlap when aStart < bEnd AND aEnd > bStart.
 */
export function rentalOverlaps(
    aStart: Date,
    aEnd: Date,
    bStart: Date,
    bEnd: Date,
): boolean {
    return aStart < bEnd && aEnd > bStart;
}
