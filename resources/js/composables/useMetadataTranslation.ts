import { useI18n } from 'vue-i18n';
import { translateMetadataKey } from '@/i18n';

export function useMetadataTranslation() {
  const { t } = useI18n();

  /**
   * Traduz uma chave de metadado
   * @param key - A chave original em inglês
   * @returns A tradução em português ou uma versão formatada da chave original
   */
  const translateKey = (key: string): string => {
    return translateMetadataKey(key);
  };

  /**
   * Traduz um objeto completo de metadados
   * @param metadata - Objeto com metadados em inglês
   * @returns Objeto com chaves traduzidas para português
   */
  const translateMetadata = (metadata: Record<string, any>): Record<string, any> => {
    const translatedMetadata: Record<string, any> = {};
    
    for (const [key, value] of Object.entries(metadata)) {
      const translatedKey = translateKey(key);
      translatedMetadata[translatedKey] = value;
    }
    
    return translatedMetadata;
  };

  /**
   * Formata um valor de metadado baseado no tipo
   * @param key - A chave do metadado
   * @param value - O valor do metadado
   * @returns Valor formatado
   */
  const formatMetadataValue = (key: string, value: any): string => {
    if (value === null || value === undefined) {
      return '-';
    }

    // Se for uma data
    if (key.toLowerCase().includes('date') || key.toLowerCase().includes('data')) {
      const date = new Date(value);
      if (!isNaN(date.getTime())) {
        return date.toLocaleDateString('pt-BR');
      }
    }

    // Se for um número e parecer ser um tamanho de arquivo
    if (key.toLowerCase().includes('size') || key.toLowerCase().includes('tamanho')) {
      const sizeInBytes = Number(value);
      if (!isNaN(sizeInBytes)) {
        return formatFileSize(sizeInBytes);
      }
    }

    // Se for um array
    if (Array.isArray(value)) {
      return value.join(', ');
    }

    // Se for um objeto
    if (typeof value === 'object') {
      return JSON.stringify(value);
    }

    return String(value);
  };

  /**
   * Formata tamanho de arquivo em bytes para formato legível
   * @param bytes - Tamanho em bytes
   * @returns String formatada (ex: "1.5 MB")
   */
  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return {
    translateKey,
    translateMetadata,
    formatMetadataValue,
    formatFileSize,
    t, // Exporta também a função t do vue-i18n para uso geral
  };
}
