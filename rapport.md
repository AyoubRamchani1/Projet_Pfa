# Performance Test Report (example)

**Project**: Projet PFA

**Purpose**: Illustrate simple performance testing for the web app by simulating multiple concurrent users and measuring response times.

**Files added**:
- [tests/perf_test.py](tests/perf_test.py): Async script that simulates N concurrent users (aiohttp).
- [requirements.txt](requirements.txt): Python dependency list (`aiohttp`).

**How it works**

- For each concurrency level (default: 10, 100, 1000) the script starts that many "virtual users".
- Each virtual user performs `requests_per_user` sequential requests (default 1). The script measures per-request latency and wall-clock time to complete all requests.
- Results are saved to `tests/perf_results.json`.

**Run instructions**

1. Install dependencies:

```bash
pip install -r requirements.txt
```

2. Run the test (example targeting the local site):

```bash
python tests/perf_test.py --url "http://localhost/Projet%20PFA/home%20page/home.php" --concurrency 10 100 1000 --requests 1
```

Adjust `--url` to the page you want to stress test (encode spaces as `%20`), and change concurrency levels as needed.
**Actual results (run locally)**

- Results were written to [tests/perf_results.json](tests/perf_results.json).

| Concurrency | Total requests | Completed | Total time (s) | Avg latency (ms) | Throughput (req/s) | Errors |
|---:|---:|---:|---:|---:|---:|---:|
| 10 | 10 | 10 | 0.090 | 71.12 | 110.92 | 0 |
| 100 | 100 | 100 | 0.693 | 493.58 | 144.33 | 0 |
| 1000 | 1000 | 900 | 30.307 | 13746.23 | 29.70 | 100 |

- Note: the server responded with HTTP status `404` for the measured requests (see `status_counts` in the JSON). This indicates the tested URL returned "Not Found"; update `--url` to a valid route to measure real application endpoints.
**Interpreting results**

- `Total time (s)`: wall-clock time to complete the test at that concurrency.
- `Avg latency (ms)`: average request latency measured per-request.
- `Throughput (req/s)`: completed requests divided by `Total time`.
- `Errors`: number of requests that raised exceptions or failed.

**Next steps / recommendations**

- Run tests while monitoring server CPU, memory, and webserver logs.
- Increase `--requests` if you want each virtual user to perform multiple sequential operations.
- Consider tools like `ab`, `wrk`, or `siege` for more advanced load testing; this script is meant as a minimal, easy-to-run starting point.
