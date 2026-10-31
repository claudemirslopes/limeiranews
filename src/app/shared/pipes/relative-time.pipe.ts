import { Pipe, PipeTransform } from '@angular/core';

const MINUTE = 60;
const HOUR = 3600;
const DAY = 86400;

/** "Há 12 minutos", "Há 3 horas", "Ontem"; acima de 7 dias mostra a data. */
@Pipe({ name: 'relativeTime' })
export class RelativeTimePipe implements PipeTransform {
  private readonly rtf = new Intl.RelativeTimeFormat('pt-BR', { numeric: 'auto' });

  transform(value: string | Date | null | undefined): string {
    if (!value) return '';
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const diff = Math.round((date.getTime() - Date.now()) / 1000);
    const abs = Math.abs(diff);

    if (abs < MINUTE) return 'Agora há pouco';
    if (abs < HOUR) return this.capitalize(this.rtf.format(Math.round(diff / MINUTE), 'minute'));
    if (abs < DAY) return this.capitalize(this.rtf.format(Math.round(diff / HOUR), 'hour'));
    if (abs < 7 * DAY) return this.capitalize(this.rtf.format(Math.round(diff / DAY), 'day'));

    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(date);
  }

  private capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
  }
}
