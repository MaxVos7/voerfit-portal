/** One day of a cow on the cow overview chart. */
export type ChartDay = {
    date: string;
    daysInMilk: number | null;
    milkProduction: number | null;
    feedN1: number;
    feedN1Original: number;
    feedN3: number;
    feedN3Original: number;
    restFeedN1: number;
    restFeedN3: number;
    milkings: number | null;
    refusals: number | null;
};

export type ChartBcs = { date: string; bcs: number; sem: number | null };

export type ChartMilkSample = {
    date: string;
    milk: number | null;
    fpcm: number | null;
    fat: number | null;
    protein: number | null;
    lactose: number | null;
    urea: number | null;
    cellCount: number | null;
};
