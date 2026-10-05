import http from 'k6/http';
import { check } from 'k6';

export const options = {
  vus: 10,
  duration: '30s',
};

export default function () {
  const res = http.get('http://localhost/moka_mock_api/api/products');

  check(res, {
    'status 200': (r) => r.status === 200,
    'response success': (r) => r.body.includes('"status": "success"'),
  });
}