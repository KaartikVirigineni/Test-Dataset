FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ExpenseTracker.sln .
COPY ExpenseTracker/ExpenseTracker.csproj ExpenseTracker/
RUN dotnet restore

COPY . .
WORKDIR /src/ExpenseTracker
RUN dotnet build -c Release -o /app/build

FROM build AS publish
RUN dotnet publish -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
COPY --from=publish /app/publish .

COPY openapi.yaml /app/openapi.yaml

EXPOSE 8080

ENV ASPNETCORE_URLS=http://+:8080

ENTRYPOINT ["dotnet", "ExpenseTracker.dll"]