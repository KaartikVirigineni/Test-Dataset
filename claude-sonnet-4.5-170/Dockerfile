FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ReserveIt.sln ./
COPY ReserveIt/ReserveIt.csproj ReserveIt/
RUN dotnet restore

COPY . .
WORKDIR /src/ReserveIt
RUN dotnet build -c Release -o /app/build

FROM build AS publish
RUN dotnet publish -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
COPY --from=publish /app/publish .
COPY openapi.yaml /app/openapi.yaml

ENV ASPNETCORE_URLS=http://+:8000
EXPOSE 8000

ENTRYPOINT ["dotnet", "ReserveIt.dll"]