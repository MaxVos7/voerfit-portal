<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import LogoutButton from '@/components/farm/LogoutButton.vue';
import { Button } from '@/components/ui/button';
import { dayOverview } from '@/routes';

type Hold = {
    cowNumber: number | null;
    feedNumber: number;
    currentKg: number | null;
    targetKg: number | null;
    lastStepDate: string | null;
};

const props = defineProps<{
    rules: Record<string, string>;
    holds: Hold[];
    distribution: { delta: number | null; cows: number }[];
}>();

function rule(name: string): string {
    return props.rules[name] ?? '?';
}

function kg(value: number | null): string {
    return value === null ? '' : value.toFixed(2).replace('.', ',');
}

function deviation(delta: number | null): string {
    if (delta === null) {
        return '';
    }

    return `${delta > 0 ? '+' : ''}${delta.toString().replace('.', ',')}`;
}

function shortDate(isoDate: string | null): string {
    if (isoDate === null) {
        return '';
    }

    const [, month, day] = isoDate.split('-');

    return `${day}-${month}`;
}
</script>

<template>
    <Head title="Voerregels" />

    <div class="flex min-h-screen flex-col gap-4 bg-muted/40 p-4">
        <header class="flex flex-wrap items-center gap-2">
            <Button as-child>
                <Link :href="dayOverview()">
                    <ArrowLeft />
                    Dagoverzicht koeien
                </Link>
            </Button>
            <LogoutButton />
        </header>

        <section class="rounded-lg border bg-background px-4 py-3">
            <h1 class="mb-2 text-lg font-semibold">
                Voerregels — zo stuurt het systeem het krachtvoer
            </h1>
            <p>
                Twee lagen: het <b>wekelijkse advies</b> (AI-model, woensdag
                06:00) bepaalt per proefkoe de afwijking t.o.v. het voerschema,
                en de <b>dagelijkse voermotor</b> (elk uur) zet die afwijking
                veilig om naar de robot.
            </p>
            <p class="mt-2 text-sm text-muted-foreground italic">
                Alle getallen op deze pagina komen live uit de
                systeemconfiguratie — wijzigt een instelling, dan verandert dit
                overzicht automatisch mee.
            </p>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-semibold">
                    1 · Wekelijks advies (woensdag 06:00)
                </h2>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Het model voorspelt de <b>melkgift per koe</b> en
                        vergelijkt met de werkelijke gift (laatste
                        <b>{{ rule('eval_days') }}</b> dagen).
                    </li>
                    <li>
                        Advies per koe:
                        <b
                            >+{{ rule('weekly_step') }} / 0 / −{{
                                rule('weekly_step')
                            }}
                            kg</b
                        >
                        t.o.v. haar huidige afwijking.
                    </li>
                    <li>
                        <b>Maximaal ±{{ rule('weekly_cap') }} kg</b> totale
                        afwijking van het schema.
                    </li>
                    <li>
                        <b
                            >Maximaal {{ rule('weekly_step') }} kg verandering
                            per week</b
                        >
                        — geldt voor álle paden, ook herstel.
                    </li>
                    <li>
                        Plotselinge melkdaling
                        <b>&gt;{{ rule('sudden_drop') }} kg/dag</b> → geen
                        wijziging (mogelijk zieke koe).
                    </li>
                    <li>
                        Deelname proef: lacterend,
                        <b>&gt;{{ rule('min_dim') }} dagen in lactatie</b>,
                        <b>&gt;{{ rule('min_du') }} dagen tot droogzetten</b>.
                    </li>
                    <li>
                        In e-mail en grafieken:
                        <b>stalnummer (intern id)</b>, bijv.
                        <i>koe 674 (id 535)</i>.
                    </li>
                </ul>
            </section>

            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-semibold">
                    2 · BCS-bewaking (conditiebescherming)
                </h2>
                <p class="mb-2">
                    Eerste regel die past geldt (BCS = recente camera-score, max
                    <b>{{ rule('bcs_age') }} dagen</b> oud):
                </p>
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-1 pr-4 font-medium">Conditie</th>
                            <th class="py-1 font-medium">Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="py-1 pr-4">
                                BCS <b>&lt; {{ rule('bcs_force') }}</b>
                            </td>
                            <td>
                                <b>+{{ rule('weekly_step') }} kg</b> (verplicht
                                omhoog)
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-1 pr-4">
                                BCS <b>&lt; {{ rule('bcs_trend') }}</b> én
                                <b>dalend</b> (≥ {{ rule('bcs_drop') }} daling
                                per {{ rule('bcs_window') }} dgn)
                            </td>
                            <td>
                                <b>+{{ rule('weekly_step') }} kg</b>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-1 pr-4">
                                BCS <b>&lt; {{ rule('bcs_hold') }}</b>
                            </td>
                            <td>
                                <b>niet verlagen</b> (vasthouden of verhogen
                                mag)
                            </td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4">
                                BCS ≥ {{ rule('bcs_hold') }} of geen recente
                                score
                            </td>
                            <td>modeladvies geldt</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-2">
                    De bewaking kijkt elke week opnieuw: herstelt een koe boven
                    {{ rule('bcs_hold') }}, dan gelden direct weer de normale
                    regels (afbouw met max {{ rule('weekly_step') }} kg/week).
                </p>
            </section>

            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-semibold">
                    3 · Dagelijkse voermotor (elk uur)
                </h2>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        <b
                            >Stapbegrenzing: max {{ rule('engine_step') }} kg
                            per dag</b
                        >
                        omhoog én omlaag, per voersoort.
                    </li>
                    <li>
                        Absolute grenzen: voer 1 en 2:
                        <b>0–{{ rule('bound_f12_max') }} kg</b>; voer 3:
                        <b>0–{{ rule('bound_f3_max') }} kg</b>.
                    </li>
                    <li>
                        Niet gestuurd: koeien
                        <b>≤ {{ rule('excl_dim') }} dagen in lactatie</b>
                        (opstart) en
                        <b>&lt; {{ rule('excl_du') }} dagen tot droogzetten</b>
                        (afbouwfase).
                    </li>
                    <li>
                        <b>Schema-bewaking</b>: toont de robot nog exact de
                        waarde die wij gisteren schreven, dan is het schema die
                        nacht niet ververst → koe wordt <b>vastgehouden</b>. Bij
                        <b>≥ {{ rule('stale_ratio_pct') }}%</b> bevroren
                        voerposities (min. {{ rule('stale_min_legs') }}
                        gemeten) wordt níets toegepast en volgt een alarmmail.
                    </li>
                    <li>
                        Voersoorten op <b>0 kg</b> tellen hierin niet mee (een
                        lege voersoort is geen storing).
                    </li>
                </ul>
            </section>

            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-semibold">4 · Droogzet-overgang</h2>
                <p class="mb-2">
                    Verlaat een koe de proef (richting droogzetten), dan mag
                    haar voer <b>niet terugspringen</b> naar het schema:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Voer mag <b>nooit stijgen</b> boven de laatst toegepaste
                        waarde.
                    </li>
                    <li>
                        Afbouw met <b>max {{ rule('hold_step') }} kg/dag</b>
                        richting haar
                        <b
                            >laagste niveau van de afgelopen
                            {{ rule('hold_lookback') }} dagen</b
                        >.
                    </li>
                    <li>Het schema wordt alléén <b>omlaag</b> gevolgd.</li>
                    <li>
                        Vrijgave zodra het schema haar niveau bereikt, bij
                        droogzetten, of als ze terugkeert in de proef.
                    </li>
                </ul>
            </section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-medium">
                    Actieve droogzet-overgangen (live)
                </h2>
                <table class="w-full border-collapse text-sm tabular-nums">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-1 pr-4 font-medium">Koenr.</th>
                            <th class="py-1 pr-4 font-medium">Voer</th>
                            <th class="py-1 pr-4 text-right font-medium">
                                Huidig (kg)
                            </th>
                            <th class="py-1 pr-4 text-right font-medium">
                                Doel (kg)
                            </th>
                            <th class="py-1 text-right font-medium">
                                Laatste stap
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="hold in holds"
                            :key="`${hold.cowNumber}-${hold.feedNumber}`"
                            class="border-b"
                        >
                            <td class="py-1 pr-4">{{ hold.cowNumber }}</td>
                            <td class="py-1 pr-4">{{ hold.feedNumber }}</td>
                            <td class="py-1 pr-4 text-right">
                                {{ kg(hold.currentKg) }}
                            </td>
                            <td class="py-1 pr-4 text-right">
                                {{ kg(hold.targetKg) }}
                            </td>
                            <td class="py-1 text-right">
                                {{ shortDate(hold.lastStepDate) }}
                            </td>
                        </tr>
                        <tr v-if="holds.length === 0">
                            <td
                                colspan="5"
                                class="py-6 text-center text-muted-foreground"
                            >
                                Geen actieve overgangen.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-lg border bg-background px-4 py-3">
                <h2 class="mb-2 font-medium">
                    Huidige proefverdeling krachtvoer (live)
                </h2>
                <table class="w-full border-collapse text-sm tabular-nums">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-1 pr-4 font-medium">
                                Afwijking (kg)
                            </th>
                            <th class="py-1 text-right font-medium">
                                Aantal koeien
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in distribution"
                            :key="row.delta ?? 'none'"
                            class="border-b"
                        >
                            <td class="py-1 pr-4">
                                {{ deviation(row.delta) }}
                            </td>
                            <td class="py-1 text-right">{{ row.cows }}</td>
                        </tr>
                        <tr v-if="distribution.length === 0">
                            <td
                                colspan="2"
                                class="py-6 text-center text-muted-foreground"
                            >
                                Geen koeien in de proef.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
