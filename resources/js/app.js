import './bootstrap';
import { parseISO, addHours, format, addDays } from 'date-fns';
import Chart from 'chart.js/auto';
window.Chart = Chart;

import { AllCommunityModule, createGrid, ModuleRegistry } from 'ag-grid-community';

ModuleRegistry.registerModules([
    AllCommunityModule,
]);

export function TimelineBarRendererByShift(params) {
    const data = params.data ?? {};
    const labelsByShift = data.LabelsByShift ?? {};

    const container = document.createElement('div');

    container.className = 'timeline-container';

    /**
     * Renderiza un turno.
     */
    const renderShift = (shiftId) => {
        const labels = labelsByShift[shiftId] ?? [];

        const totalPlanned = labels.reduce(
            (sum, label) =>
                sum + Number(label.PlannedQuantity ?? 0),
            0
        );

        /**
         * El turno siempre ocupa el 50%.
         *
         * Si no tiene producción, mostramos
         * un bloque gris.
         */
        if (totalPlanned <= 0) {
            const emptyShift =
                document.createElement('div');

            emptyShift.className =
                'timeline-shift timeline-shift-empty';

            return emptyShift;
        }

        const shiftContainer =
            document.createElement('div');

        shiftContainer.className =
            'timeline-shift';

        labels.forEach((label, index) => {
            const planned =
                Number(label.PlannedQuantity ?? 0);

            const completed =
                Number(label.ProducedQuantity ?? 0);

            /**
             * No mostramos labels sin
             * cantidad planeada.
             */
            if (planned <= 0) {
                return;
            }

            /**
             * Porcentaje producido.
             */
            const completedPct =
                Math.min(
                    completed / planned,
                    1
                ) * 100;

            /**
             * Contenedor del label.
             */
            const labelContainer =
                document.createElement('div');

            labelContainer.className =
                'timeline-label';

            /**
             * Borde entre labels.
             */
            if (index < labels.length - 1) {
                labelContainer.classList.add(
                    'timeline-label-divider'
                );
            }

            /**
             * Ancho proporcional a PlannedQuantity.
             */
            labelContainer.style.width =
                `${(planned / totalPlanned) * 100}%`;

            /**
             * Barra de progreso.
             */
            const progress =
                document.createElement('div');

            progress.className =
                'timeline-progress';

            progress.style.width =
                `${completedPct}%`;

            /**
             * Shop Order.
             */
            const text =
                document.createElement('span');

            text.className =
                'timeline-label-text';

            text.textContent =
                label.ShopOrderNumber ?? '';

            text.title =
                label.ShopOrderNumber ?? '';

            /**
             * Construimos:
             *
             * labelContainer
             *   ├── progress
             *   └── text
             */
            labelContainer.appendChild(
                progress
            );

            labelContainer.appendChild(
                text
            );

            shiftContainer.appendChild(
                labelContainer
            );
        });

        return shiftContainer;
    };

    /**
     * Turno 1 = 50%
     * Turno 2 = 50%
     */
    container.appendChild(
        renderShift(1)
    );

    container.appendChild(
        renderShift(2)
    );

    return container;
}


const TimelineBarRenderer = (params) => {
    const container = document.createElement('div');

    container.className = 'w-full';

    console.log(params.data);

    const hour =
        params.colDef.cellRendererParams.hour;

    const labels =
        params.data?.Labels?.[hour] ?? [];

    labels.forEach((label) => {
        const labelElement =
            document.createElement('div');

        labelElement.className =
            'mb-1 text-center text-xs font-medium';

        labelElement.style.backgroundColor =
            label.LabelIsCompleted
                ? '#ffeb3b'
                : '#f2dede';

        labelElement.textContent =
            label.LabelCode ?? '';

        container.appendChild(
            labelElement
        );
    });

    return container;
};

function generateHours(selectedDate) {
    const start = parseISO(selectedDate);

    start.setHours(8, 0, 0, 0);

    return Array.from({ length: 24 }, (_, index) => {
        const hour = addHours(start, index);

        return {
            index,
            label: format(hour, 'yyyyMMdd HH'),
            hour: format(hour, 'HH:00'),
        };
    });
}

window.productionGrid = function (initialOrders, initialDate) {
    return {
        gridApi: null,
        hours: [],
        date: initialDate,
        init() {
            this.hours = generateHours(this.date);

            const currentDateHour = format(new Date(), 'yyyyMMdd HH');

            const gridOptions = {
                headerHeight: 23,
                rowHeight: 23,
                domLayout: 'autoHeight',
                suppressRowVirtualisation: true,
                columnDefs: [
                    {
                        field: 'ProductionSequence',
                        headerName: 'Ordering',
                        pinned: 'left',
                        width: 70,
                        cellClass: 'text-center',
                    },
                    {
                        field: 'ProductCode',
                        headerName: 'Product Code',
                        pinned: 'left',
                        width: 120,
                    },
                    {
                        field: 'WorkcenterName',
                        headerName: 'Workcenter Name',
                        pinned: 'left',
                        width: 120,
                    },
                    {
                        field: 'StandardPackNumber',
                        headerName: 'SNP',
                        pinned: 'left',
                        cellClass: 'text-center',
                        width: 70,
                    },
                    {
                        field: 'CompletedPercentage',
                        headerName: 'Completed %',
                        pinned: 'left',
                        cellClass: 'text-center',
                        width: 95,
                    },
                    ...this.hours.map((hour) => ({
                        colId: `hour_${hour.index}`,
                        field: 'labels',
                        headerName: hour.hour,
                        flex: 1,
                        minWidth: 60,
                        width: 60,
                        sortable: false,
                        headerClass:
                            hour.label === currentDateHour
                                ? 'current-hour-header'
                                : '',
                        ...(hour.index === 0 && {
                            colSpan: (params) => params.data ? this.hours.length : 1,
                            cellRenderer: TimelineBarRendererByShift,
                        }),
                    })),
                ],
                rowData: initialOrders,
                suppressHorizontalScroll: true,
            };
            createGrid(
                this.$refs.grid,
                gridOptions
            );
        },
        previousDay() { this.date = format(addDays(parseISO(this.date), -1), 'yyyy-MM-dd'); this.reloadGrid(); },
        nextDay() { this.date = format(addDays(parseISO(this.date), 1), 'yyyy-MM-dd'); this.reloadGrid(); },
        reloadGrid() {

        }
    };
};
