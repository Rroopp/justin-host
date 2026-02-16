import requests

def test_home_page_health_check():
    base_url = "http://127.0.0.1:8000"
    try:
        response = requests.get(base_url, timeout=30)
        response.raise_for_status()
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
    except requests.RequestException as e:
        assert False, f"Request to home page failed: {e}"

test_home_page_health_check()