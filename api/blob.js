import { del, get, put } from '@vercel/blob';

export default async function handler(request, response) {
  if (request.method !== 'POST') {
    response.status(405).json({ error: 'Método não permitido.' });
    return;
  }

  const expectedSecret = process.env.BLOB_PROXY_SECRET || '';
  const receivedSecret = request.headers['x-symo-blob-secret'] || '';
  if (!expectedSecret || receivedSecret !== expectedSecret) {
    response.status(403).json({ error: 'Acesso não autorizado.' });
    return;
  }

  try {
    const { action, pathname, contentType, data, url } = request.body || {};

    if (action === 'upload') {
      if (!pathname || !contentType || !data) {
        response.status(400).json({ error: 'Dados de upload incompletos.' });
        return;
      }

      const blob = await put(pathname, Buffer.from(data, 'base64'), {
        access: 'private',
        contentType,
        addRandomSuffix: false,
      });

      response.status(200).json(blob);
      return;
    }

    if (action === 'delete') {
      if (!url) {
        response.status(400).json({ error: 'URL do Blob não informada.' });
        return;
      }

      await del(url);
      response.status(200).json({ deleted: true });
      return;
    }

    if (action === 'download') {
      if (!url) {
        response.status(400).json({ error: 'URL do Blob não informada.' });
        return;
      }

      const result = await get(url, { access: 'private' });
      if (!result || !result.stream) {
        response.status(404).json({ error: 'Imagem não encontrada.' });
        return;
      }

      const chunks = [];
      for await (const chunk of result.stream) {
        chunks.push(Buffer.from(chunk));
      }

      response.status(200).json({
        contentType: result.blob.contentType || 'application/octet-stream',
        data: Buffer.concat(chunks).toString('base64'),
      });
      return;
    }

    response.status(400).json({ error: 'Operação de Blob inválida.' });
  } catch (error) {
    console.error('Vercel Blob operation failed', error);
    response.status(502).json({ error: 'Falha na operação do armazenamento.' });
  }
}