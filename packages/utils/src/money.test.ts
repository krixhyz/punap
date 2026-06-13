import { describe, it, expect } from 'vitest';
import { toPaisa, toMoney, formatNPR } from './money.js';

describe('toPaisa', () => {
    it('converts 10.5 NPR to 1050 paisa', () => {
        expect(toPaisa(10.5)).toBe(1050);
    });

    it('converts whole numbers correctly', () => {
        expect(toPaisa(100)).toBe(10000);
        expect(toPaisa(0)).toBe(0);
    });

    it('handles floating point amounts correctly', () => {
        expect(toPaisa(99.99)).toBe(9999);
        expect(toPaisa(1.01)).toBe(101);
    });
});

describe('toMoney', () => {
    it('converts 1050 paisa to 10.5 NPR', () => {
        expect(toMoney(1050)).toBe(10.5);
    });

    it('round-trips with toPaisa', () => {
        expect(toMoney(toPaisa(10.5))).toBe(10.5);
        expect(toMoney(toPaisa(99.99))).toBe(99.99);
    });
});

describe('formatNPR', () => {
    it('formats amount with NPR prefix and 2dp', () => {
        expect(formatNPR(10.5)).toBe('NPR 10.50');
        expect(formatNPR(1000)).toBe('NPR 1000.00');
    });
});
