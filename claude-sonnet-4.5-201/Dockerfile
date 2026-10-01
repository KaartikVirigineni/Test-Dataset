FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

RUN python init_db.py

EXPOSE 6543

CMD ["waitress-serve", "--host=0.0.0.0", "--port=6543", "--call", "app:main"]