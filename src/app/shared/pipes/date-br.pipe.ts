import { Pipe, PipeTransform } from '@angular/core';

export type DateBrFormat = 'short' | 'long' | 'datetime' | 'time';

const FORMATS: Record<DateBrFormat, Intl.DateTimeFormatOptions> = {
  short: { day: '2-digit', month: '2-digit', year: 'numeric' },
  long: { day: '2-digit', month: 'long', year: 'numeric' },
  datetime: {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  },
  time: { hour: '2-digit', minute: '2-digit' },
};

/** Formata datas ISO em pt-BR. Ex.: `{{ iso | dateBr:'long' }}` → 29 de outubro de 2026. */
@Pipe({ name: 'dateBr' })
export class DateBrPipe implements PipeTransform {
  transform(value: string | Date | null | undefined, format: DateBrFormat = 'short'): string {
    if (!value) return '';
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('pt-BR', FORMATS[format]).format(date);
  }
}
