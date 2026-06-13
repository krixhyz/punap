import { describe, it, expect } from 'vitest';
import { rentalOverlaps } from './dates.js';

const d = (s: string) => new Date(s);

describe('rentalOverlaps', () => {
    it('detects overlapping ranges', () => {
        // [Jan 1 – Jan 5] overlaps with [Jan 3 – Jan 7]
        expect(rentalOverlaps(d('2024-01-01'), d('2024-01-05'), d('2024-01-03'), d('2024-01-07'))).toBe(true);
    });

    it('detects containment', () => {
        // [Jan 1 – Jan 10] contains [Jan 3 – Jan 7]
        expect(rentalOverlaps(d('2024-01-01'), d('2024-01-10'), d('2024-01-03'), d('2024-01-07'))).toBe(true);
    });

    it('returns false for non-overlapping ranges', () => {
        // [Jan 1 – Jan 5] and [Jan 6 – Jan 10] are adjacent but not overlapping
        expect(rentalOverlaps(d('2024-01-01'), d('2024-01-05'), d('2024-01-06'), d('2024-01-10'))).toBe(false);
    });

    it('returns false when second range ends before first starts', () => {
        expect(rentalOverlaps(d('2024-01-10'), d('2024-01-15'), d('2024-01-01'), d('2024-01-09'))).toBe(false);
    });

    it('returns false for touching boundaries (half-open intervals)', () => {
        // aEnd === bStart means they touch but do not overlap
        expect(rentalOverlaps(d('2024-01-01'), d('2024-01-05'), d('2024-01-05'), d('2024-01-10'))).toBe(false);
    });
});
