#!/usr/bin/env python3
"""
Simple asyncio-based performance tester using aiohttp.

For each concurrency level this script starts N concurrent "users",
each performing `requests_per_user` sequential requests to the target URL.

Example:
  pip install -r requirements.txt
  python tests/perf_test.py --url "http://localhost/Projet%20PFA/home%20page/home.php" --concurrency 10 100 1000 --requests 1

The script writes a JSON file with per-test statistics (default: tests/perf_results.json).
"""

import argparse
import asyncio
import time
import statistics
import json
import sys

try:
    import aiohttp
except Exception:
    print("Missing dependency: aiohttp. Run: pip install -r requirements.txt", file=sys.stderr)
    raise


async def user_task(session, url, requests_per_user, results, errors, idx):
    for _ in range(requests_per_user):
        t0 = time.perf_counter()
        try:
            async with session.get(url) as resp:
                await resp.read()
                dt = time.perf_counter() - t0
                results.append({"latency_s": dt, "status": resp.status})
        except Exception as e:
            errors.append(str(e))


async def run_test(url, concurrency, requests_per_user, timeout):
    results = []
    errors = []
    total_requests = concurrency * requests_per_user
    timeout_obj = aiohttp.ClientTimeout(total=timeout)
    connector = aiohttp.TCPConnector(limit=None)
    async with aiohttp.ClientSession(timeout=timeout_obj, connector=connector) as session:
        tasks = [asyncio.create_task(user_task(session, url, requests_per_user, results, errors, i)) for i in range(concurrency)]
        start = time.perf_counter()
        await asyncio.gather(*tasks)
        total_time = time.perf_counter() - start

    latencies = [r["latency_s"] for r in results]
    status_counts = {}
    for r in results:
        status_counts[r["status"]] = status_counts.get(r["status"], 0) + 1

    stats = {
        "concurrency": concurrency,
        "total_requests": total_requests,
        "completed_requests": len(results),
        "errors": len(errors),
        "status_counts": status_counts,
        "total_time_s": total_time,
        "throughput_rps": (len(results) / total_time) if total_time > 0 else None,
    }
    if latencies:
        lat_sorted = sorted(latencies)
        def pct(p):
            idx = int(round((p/100) * (len(lat_sorted)-1)))
            return lat_sorted[idx] * 1000
        stats.update({
            "avg_latency_ms": statistics.mean(latencies)*1000,
            "p50_ms": pct(50),
            "p95_ms": pct(95),
            "p99_ms": pct(99),
            "min_ms": min(latencies)*1000,
            "max_ms": max(latencies)*1000,
        })
    return stats


def parse_args(argv=None):
    parser = argparse.ArgumentParser(description="Async performance tester (simple)")
    parser.add_argument("--url", required=False, default="http://127.0.0.1/", help="Target URL to test")
    parser.add_argument("--concurrency", nargs="+", type=int, default=[10,100,1000], help="List of concurrency levels to run")
    parser.add_argument("--requests", type=int, default=1, help="Requests per virtual user")
    parser.add_argument("--timeout", type=int, default=30, help="Per-request timeout (seconds)")
    parser.add_argument("--output", default="tests/perf_results.json", help="Output JSON file for results")
    return parser.parse_args(argv)


async def main(argv=None):
    args = parse_args(argv)
    all_stats = []
    for c in args.concurrency:
        print(f"Running: concurrency={c}, requests_per_user={args.requests}")
        stats = await run_test(args.url, c, args.requests, args.timeout)
        print(f"-> completed: {stats['completed_requests']} reqs in {stats['total_time_s']:.3f}s (throughput={stats['throughput_rps']:.1f} rps)")
        all_stats.append(stats)
    # save results
    out_path = args.output
    try:
        with open(out_path, "w", encoding="utf-8") as f:
            json.dump(all_stats, f, indent=2)
        print(f"Saved results to {out_path}")
    except Exception as e:
        print(f"Failed to save results: {e}", file=sys.stderr)


if __name__ == "__main__":
    asyncio.run(main())
